<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\UnitUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use NotificationChannels\Fcm\FcmChannel;

class EventUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;

    public $body;

    protected $event;

    protected $unitUser;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Model $event, UnitUser $unitUser)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->event = $event;
        $this->unitUser = $unitUser;

        if ($this->event->is_cancel == false) {
            $this->title = 'Update event';
            $this->body = $event->title.' event has been updated!';
        } else {
            $this->title = 'Cancel event';
            $this->body = $event->title.' event has been cancelled!';
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
            'model_type' => 'App\Models\Event',
            'model_id' => $this->event->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->unitUser->unit_id,
        ];
    }
}
