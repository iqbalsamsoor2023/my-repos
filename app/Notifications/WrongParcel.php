<?php

namespace App\Notifications;

use App\Actions\Notification\CountUnreadNotificationAction;
use App\Channels\HuaweiChannel;
use App\Models\Parcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class WrongParcel extends Notification implements ShouldQueue
{
    use Queueable;

    protected $parcel;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Parcel $parcel, CountUnreadNotificationAction $countUnreadNotificationAction)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'PmTalk';
        $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');

        $this->parcel = $parcel;
        $this->count_unread_notification_action = $countUnreadNotificationAction;

        if (App::getLocale() == 'th') {
            $this->title = 'This is not my Parcel ';
            $this->body = 'This is not my parcel from '.$parcel->unit->unit_number.', '.$parcel->unit->residence->name.'.';
        } else {
            $this->title = 'This is not my Parcel ';
            $this->body = 'This is not my parcel from '.$parcel->unit->unit_number.', '.$parcel->unit->residence->name.'.';
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = [];
        if (isset($notifiable) && $notifiable->devices()->count() > 0) {
            $huaweiDevices = $notifiable->devices()
                ->whereNotNull('huawei_token')
                ->pluck('huawei_token')
                ->toArray();
            if (count($huaweiDevices) > 0) {
                $channels[] = HuaweiChannel::class;
            }
            $channels[] = FcmChannel::class;
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'model_type' => 'App\Models\Parcel',
            'model_id' => $this->parcel->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->parcel->unit_id,
        ];
    }
}
