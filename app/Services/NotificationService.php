<?php

namespace App\Services;

use App\Actions\Notification\GetNotificationAction;
use App\Actions\Notification\GetTotalUnreadNotificationAction;
use App\Actions\Notification\ReadNotificationAction;
use App\Enums\IncidentReport\RecipientEnum;
use App\Http\Requests\Notification\GetNotificationRequest;
use App\Models\Notification;
use App\Models\Residence;
use App\Models\UnitUser;
use App\Models\User;
use App\Notifications\IncidentReportCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    public function index(GetNotificationRequest $request)
    {
        $getNotificationAction = new GetNotificationAction;

        $request->merge([
            'type' => 'Filament\\Notifications\\DatabaseNotification',
        ]);

        $notification = $getNotificationAction->execute($request);

        return $notification;
    }

    public function update(string $id)
    {
        $notification = Notification::whereId($id);
        $readNotificationAction = new ReadNotificationAction;
        $notification = $readNotificationAction->execute($notification);

        return $notification;
    }

    public function unreadNotification(Request $request)
    {
        $getTotalUnreadNotificationAction = new GetTotalUnreadNotificationAction;

        return $getTotalUnreadNotificationAction->execute($request);
    }

    public function sendNotificationToResident(array $notificationData)
    {
        $residence = Residence::find($notificationData['mmb_residence_id']);

        if ($notificationData['recipient'] == RecipientEnum::JURISTIC->value) {
            // Notify PM
            $pmUser = User::findOrFail($residence->property_management_user_id);
            $pmUser->notify(new IncidentReportCreated($notificationData));
        } else {
            // Notify unit users if unit_id exists, otherwise notify PM
            if (!empty($notificationData['mmb_unit_id'])) {
                $unitUsers = UnitUser::where('unit_id', $notificationData['mmb_unit_id'])->get();
                foreach ($unitUsers as $unitUser) {
                    NotificationFacade::send($unitUser->user, new IncidentReportCreated($notificationData));
                }
            } else {
                $pmUser = User::findOrFail($residence->property_management_user_id);
                $pmUser->notify(new IncidentReportCreated($notificationData));
            }
        }
    }
}
