<?php

namespace App\Actions\Notification;

class ReadNotificationAction
{
    public function execute($notification)
    {
        return $notification->update(['read_at' => now()]);
    }
}
