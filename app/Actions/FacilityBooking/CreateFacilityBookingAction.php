<?php

namespace App\Actions\FacilityBooking;

use App\Actions\Notification\CountUnreadNotificationAction;
use App\Enums\FacilityBooking\FacilityBookingStatus;
use App\Exceptions\GeneralException;
use App\Http\Requests\FacilityBooking\StoreFacilityBookingRequest;
use App\Models\AmenityBooking;
use App\Models\Residence;
use App\Models\User;
use App\Notifications\AmenityBookingCreated;
use Filament\Notifications\Notification as FilamentsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class CreateFacilityBookingAction
{
    public function execute(StoreFacilityBookingRequest $request)
    {
        $request->merge([
            'status' => FacilityBookingStatus::PENDING->value,
        ]);

        $facility_booking = AmenityBooking::create($request->only([
            'facility_id',
            'user_id',
            'unit_id',
            'ref_no',
            'start_at',
            'end_at',
            'status',
            'created_by',
        ]));

        if (! $facility_booking) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed booking faciliy');
        }

        $this->sendFacilityBookingCreatedNotification($facility_booking);

        return $facility_booking;
    }

    public function sendFacilityBookingCreatedNotification($facility_booking)
    {
        $count_notification_action = new CountUnreadNotificationAction; // for ios badge need server side count

        $residence = Residence::whereId($facility_booking->facility->residence_id)->firstOrFail();
        $pm_user = User::findOrFail($residence->property_management_user_id);
        $pm_user->notify(new AmenityBookingCreated($facility_booking, $count_notification_action));

        // send notification to PM dashboard
        $recipient = $pm_user;

        if (App::getLocale() == 'th') {
            FilamentsNotification::make()
                ->title('คำขอจองห้องพัก')
                ->sendToDatabase($recipient);
        } else {
            FilamentsNotification::make()
                ->title('Facility Booking Request')
                ->sendToDatabase($recipient);
        }
    }
}
