<?php

namespace App\Notifications;

use App\Channels\HuaweiChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class UserRegistered extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $user;
    public $title;
    public $body;

    public $huaweiTokenType;
    public $huaweiAppId;
    public $huaweiClientSecret;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Model $user)
    {
        $this->onQueue('NotificationQueue');

        $this->huaweiTokenType = 'PmTalk';
        $this->huaweiAppId = config('huawei.huawei-app-id.pmocPMTalk');
        $this->huaweiClientSecret = config('huawei.huawei-client-secret.pmocPMTalk');

        $this->user = $user;
        $this->title = 'New user register';
        $this->body = $user->name . ' has just registered.';
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
     * Get the Fcn=m representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return FcmMessage
     */
    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(
            notification: new FcmNotification(
                title: $this->title,
                body: $this->body
            )
        ))->custom([
            'webpush' => [
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'priority' => 'high',
                    'content_available' => true,
                ],
                'fcm_options' => [
                    'link' => 'https://www.google.com/',
                    'analytics_label' => 'analytics_web',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'content-available' => 1, // IMPORTANT (iOS format)
                    ],
                ],
            ],
        ]);
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
            'model_type' => 'App\Models\User',
            'model_id' => $this->user->id,
            'message_title' => $this->title,
            'message_body' => $this->body,
        ];
    }
}