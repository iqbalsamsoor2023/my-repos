<?php

namespace App\Services;

use App\Actions\Notification\ReadNotificationByTypeAction;
use App\Actions\Parcel\CreateParcelAction;
use App\Actions\Parcel\GetParcelAction;
use App\Actions\Parcel\UpdateParcelAction;
use App\Enums\LogisticPartner\CategoryType;
use App\Enums\LogisticPartner\ModesType;
use App\Http\Requests\Parcel\StoreParcelRequest;
use App\Http\Requests\Parcel\UpdateParcelRequest;
use App\Models\LogisticPartner;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ParcelService
{
    public function index(Request $request)
    {
        $getParcelAction = new GetParcelAction;
        $parcel = $getParcelAction->execute($request);

        return $parcel;
    }

    public function create(StoreParcelRequest $request)
    {
        $parcelAction = new CreateParcelAction;

        if (! empty($request->other_courier)) {
            $courier = LogisticPartner::firstOrCreate([
                'category' => CategoryType::Courier->value,
                'name' => $request->other_courier,
                'modes' => $request->mode ?? ModesType::Local->value,
            ]);

            $request->merge([
                'courier_id' => $courier->id,
            ]);
        }

        $parcel = $parcelAction->execute($request);

        return $parcel;
    }

    public function update(UpdateParcelRequest $request)
    {
        $parcelAction = new UpdateParcelAction;
        $parcel = $parcelAction->execute($request);

        return $parcel;
    }

    public function updateUnreadWrongParcelNotificationsForUser(int $user_id)
    {
        $user = User::find($user_id);

        if (! $user) {
            throw new Exception('User not found');
        }

        $notificationType = 'App\Notifications\WrongParcel';

        $unreadNotifications = Notification::where('notifiable_id', $user_id)
            ->where('type', $notificationType)
            ->whereNull('read_at')
            ->get();

        if ($unreadNotifications->count() > 0) {
            $readNotificationAction = new ReadNotificationByTypeAction;
            $readNotifications = $readNotificationAction->execute($notificationType, $user_id);

            return $readNotifications;
        }

        return $unreadNotifications;
    }
}
