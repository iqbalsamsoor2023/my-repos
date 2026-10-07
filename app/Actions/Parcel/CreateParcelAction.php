<?php

namespace App\Actions\Parcel;

use App\Enums\Parcel\ParcelStatus;
use App\Exceptions\GeneralException;
use App\Http\Requests\Parcel\StoreParcelRequest;
use App\Jobs\ImageProcessing\ProcessParcelImage;
use App\Models\Parcel;
use App\Models\UnitUser;
use App\Models\User;
use App\Notifications\ParcelArrived;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class CreateParcelAction
{
    public function execute(StoreParcelRequest $request)
    {
        $request->merge([
            'status' => ParcelStatus::PENDING_PICK_UP->value,
        ]);

        if ($request->role_type == 1) { // PM Role (mmb)
            $request->merge([
                'created_by_mmb_user_id' => $request->created_by,
            ]);
        } elseif ($request->role_type == 2) { // SG Role (sgoc)
            $request->merge([
                'created_by_sgoc_user_id' => $request->created_by,
            ]);
        }

        $parcel = Parcel::create($request->only([
            'unit_id',
            'receiver_id',
            'receiver_name',
            'courier_id',
            'tracking_no',
            'description',
            'status',
            'created_by_mmb_user_id',
            'created_by_sgoc_user_id',
        ]));

        if (! $parcel) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating parcel');
        }

        $this->uploadParcelImage($parcel, $request);
        $this->sendParcelArrivedNotification($parcel);

        return $parcel;
    }

    private function uploadParcelImage(Parcel $parcel, $request)
    {
        if ($request->hasFile('images')) {
            $fileType = 'parcel';
            foreach ($request->images as $image) {
                $imageExtension = $image->extension();
                $uniqueFileName = "{$fileType}_{$parcel->id}_".now()->format('YmdHis').'_'.uniqid().".{$imageExtension}";

                // Store temporarily in storage/app/temp/parcel
                $tempDir = "temp/parcel/{$parcel->id}";
                $tempPath = $image->storeAs($tempDir, $uniqueFileName);

                // Dispatch with path, not request
                ProcessParcelImage::dispatch(
                    $parcel->id,
                    $tempPath,
                    $fileType,
                    'parcel_images',
                    $uniqueFileName
                );
            }
        }
    }

    public function sendParcelArrivedNotification(Parcel $parcel)
    {
        if (is_null($parcel->receiver_id) == false) {
            // Send to one user
            $user = User::whereId($parcel->receiver_id)->hasDevice()->first();

            if ($user) {
                Notification::send($user, new ParcelArrived($parcel));
            }
        } else {
            $unitUsers = UnitUser::where('unit_id', $parcel->unit_id)->get();

            foreach ($unitUsers as $unitUser) {
                Notification::send($unitUser->user, new ParcelArrived($parcel));
            }
        }
    }
}
