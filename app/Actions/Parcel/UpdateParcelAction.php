<?php

namespace App\Actions\Parcel;

use App\Actions\Notification\CountUnreadNotificationAction;
use App\Enums\Parcel\ParcelStatus;
use App\Exceptions\GeneralException;
use App\Filament\Resources\Parcels\ParcelResource;
use App\Http\Requests\Parcel\UpdateParcelRequest;
use App\Models\Parcel;
use App\Models\User;
use App\Notifications\WrongParcel;
use App\Support\Notifications\DashboardNotification;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class UpdateParcelAction
{
    public function execute(UpdateParcelRequest $request)
    {
        $parcelIds = $request->parcel_id;

        $updateData = [
            'pickup_time' => Carbon::now(),
            'pickup_person_name' => $request->pickup_person_name,
            'pickup_person_contact_no' => $request->pickup_person_contact_no,
            'pickup_type' => $request->pickup_type,
            'status' => $request->status,
        ];

        if ($request->role_type == 1) { // PM Role (mmb)
            $updateData['updated_by_mmb_user_id'] = $request->updated_by;
        } elseif ($request->role_type == 2) {  // SG Role (sgoc)
            $updateData['updated_by_sgoc_user_id'] = $request->updated_by;
        }

        // Bulk update all parcels at once
        $updated = Parcel::whereIn('id', $parcelIds)->update($updateData);

        if (! $updated) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating parcel(s)');
        }

        // Eager load parcels with relationships for notifications
        $parcels = Parcel::with([
            'unit.residence',
            'unit.residence.receptionist',
            'unit.residence.propertyManagementUser',
        ])->whereIn('id', $parcelIds)->get();

        // Handle media upload per parcel
        foreach ($parcels as $parcel) {
            $this->uploadSignatureImage($parcel, $request);
        }

        // Group once by status and only dispatch the handler for statuses present.
        $parcelsByStatus = $parcels->groupBy('status');

        $notMyParcels = $parcelsByStatus->get(ParcelStatus::NOT_MY_PARCEL->value, collect());
        if ($notMyParcels->isNotEmpty()) {
            $this->sendNotificationForNotMyParcelStatus($notMyParcels);
        }

        $pickedUpParcels = $parcelsByStatus->get(ParcelStatus::PICKED_UP->value, collect());
        if ($pickedUpParcels->isNotEmpty()) {
            $this->sendNotificationForPickedUpStatus($pickedUpParcels);
        }

        return $parcel;
    }

    private function uploadSignatureImage(Parcel $parcel, UpdateParcelRequest $request)
    {
        if ($request->hasFile('signature_image')) {
            $parcel->addMediaFromRequest('signature_image')
                ->withCustomProperties(['type' => 'signature'])
                ->preservingOriginal()
                ->toMediaCollection('signature_image');
        }
    }

    /**
     * @param  Collection<int, Parcel>  $notMyParcels
     */
    private function sendNotificationForNotMyParcelStatus(Collection $notMyParcels)
    {
        $countNotificationAction = new CountUnreadNotificationAction; // for ios badge need server side count

        // Collect the recipients across all parcels for the push notification
        $users = $notMyParcels
            ->flatMap(fn (Parcel $parcel) => $this->parcelDashboardRecipients($parcel))
            ->unique('id');

        // Send push notifications in batch
        foreach ($notMyParcels as $parcel) {
            Notification::send(
                $users,
                new WrongParcel($parcel, $countNotificationAction)
            );
        }

        // PM dashboard notifications in batch
        foreach ($notMyParcels as $parcel) {
            $this->sendParcelDashboardNotification($parcel, ParcelStatus::NOT_MY_PARCEL);
        }
    }

    /**
     * @param  Collection<int, Parcel>  $pickedUpParcels
     */
    private function sendNotificationForPickedUpStatus(Collection $pickedUpParcels)
    {
        // PM dashboard notifications in batch
        foreach ($pickedUpParcels as $parcel) {
            $this->sendParcelDashboardNotification($parcel, ParcelStatus::PICKED_UP);
        }
    }

    /**
     * Send the PM dashboard notification for a parcel whose status just changed.
     * The status is mapped to its translation key group; a status with no dashboard
     * message is skipped, and a tracking-less title is used when the parcel has no
     * tracking number.
     */
    private function sendParcelDashboardNotification(Parcel $parcel, ParcelStatus $status): void
    {
        $keyGroup = match ($status) {
            ParcelStatus::NOT_MY_PARCEL => 'not_my_parcel',
            ParcelStatus::PICKED_UP => 'parcel_picked_up',
            default => null,
        };

        if ($keyGroup === null) {
            return;
        }

        $recipientUsers = $this->parcelDashboardRecipients($parcel);

        if ($recipientUsers->isEmpty()) {
            return;
        }

        $hasTracking = ! empty($parcel->tracking_no);

        $titleKey = $hasTracking
            ? "notification.{$keyGroup}.title"
            : "notification.{$keyGroup}.title_without_tracking";

        $params = [
            'residentName' => $parcel->receiver_name,
            'unit' => $parcel->unit?->unit_number,
        ];

        if ($hasTracking) {
            $params['trackingNo'] = $parcel->tracking_no;
        }

        DashboardNotification::make($titleKey)
            ->params($params)
            ->icon(Heroicon::InboxArrowDown, $status === ParcelStatus::NOT_MY_PARCEL ? 'danger' : 'success')
            ->viewAction(ParcelResource::getUrl('edit', ['record' => $parcel->id]))
            ->sendToDatabase($recipientUsers);
    }

    /**
     * The receptionist and property-management users (with a device) for the
     * parcel's residence.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function parcelDashboardRecipients(Parcel $parcel): \Illuminate\Support\Collection
    {
        $residence = $parcel->unit->residence;

        return collect([
            $residence->receptionist ?? null,
            $residence->propertyManagementUser ?? null,
        ])->filter(fn($user) => $user && $user->hasDevice())->unique('id');
    }
}