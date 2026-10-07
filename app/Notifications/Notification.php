<?php

namespace App\Notifications;

use App\Actions\Notification\CountUnreadNotificationAction;
use Illuminate\Notifications\Notification as IlluminateNotification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class Notification extends IlluminateNotification
{
    public $title;

    public $body;

    protected $badge;

    protected $count_unread_notification_action;

    public function getBadgeCount($notifiable)
    {
        if (! is_null($this->badge)) {
            return $this->badge;
        }

        if (! $this->count_unread_notification_action instanceof CountUnreadNotificationAction) {
            $this->count_unread_notification_action = app(CountUnreadNotificationAction::class);
        }

        $badge = $this->count_unread_notification_action->execute($notifiable->id, null, 'Notification')['total_unread_notification'] ?? null;

        return $this->badge = $badge;
    }

    protected function createDefaultFcmMessage(
        $notifiable,
        string $title,
        string $body,
        ?int $badge = null,
        array $data = [],
        array $apnsPayload = []
    ) {
        $badge = is_null($badge) ? $this->getBadgeCount($notifiable) : '';
        $apnsBadge = (int) ($badge ?: 0);

        if ($apnsBadge > 0) {
            $apnsBadge++;
        }

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body,
        )))
            ->data(array_merge($data, [
                'content_available' => 'true',
                'mutable_content' => 'true',
                'badge' => (string) ($badge ?: ''),
                'title' => $title,
                'body' => $body,
            ])
            )
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '5',
                    ],
                    'payload' => array_merge($apnsPayload, [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => (int) ($apnsBadge ?: 0),
                        ],
                    ]),
                ],
                'webpush' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'icon_url', // Optional: Provide the URL of the icon to display with the notification
                        'badge' => (int) ($badge ?: 0), // Optional: Provide a badge number
                        'data' => [
                            'content_available' => 'true',
                            'mutable_content' => 'true',
                        ],
                    ],
                    'fcm_options' => [
                        'link' => 'https://google.com/', // URL to open when the notification is clicked
                        'analytics_label' => 'analytics_web', // Optional: Analytics label
                    ],

                ],
            ]);
    }

    public function toFcm($notifiable)
    {
        return $this->createDefaultFcmMessage(
            notifiable: $notifiable,
            title: $this->title,
            body: $this->body
        );
    }
}
