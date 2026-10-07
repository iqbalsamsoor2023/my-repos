<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use App\Models\Residence;
use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class IncidentReportCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $notificationData;

    public $huaweiTokenType;

    public $huaweiAppId;

    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($notificationData)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'Mymooban';
        $this->huaweiAppId = config('huawei.huawei-app-id.mymooban');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.mymooban');

        $this->notificationData = $notificationData;

        if (is_null($this->notificationData['mmb_unit_id']) == false) {
            $unit = Unit::where('id', $this->notificationData['mmb_unit_id'])->first();

            $title = 'Dear resident in '.$unit->unit_number.', we found something wrong with your house. Please check';
        } else {
            $residence = Residence::where('id', $this->notificationData['mmb_residence_id'])->first();

            $title = 'we found something wrong from'.$residence->name.'. Please check';
        }

        if (App::getLocale() == 'th') {
            $this->title = $title;
            $this->body = 'The description is: '.$this->notificationData['description'];
        } else {
            $this->title = $title;
            $this->body = 'The description is: '.$this->notificationData['description'];
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
            'model_type' => 'App\Models\IncidentReport',
            'model_id' => $this->notificationData['id'],
            'message_title' => $this->title,
            'message_body' => $this->body,
            'unit_id' => $this->notificationData['mmb_unit_id'],
        ];
    }
}
