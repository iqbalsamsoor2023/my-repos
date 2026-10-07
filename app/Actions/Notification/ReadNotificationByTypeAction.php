<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class ReadNotificationByTypeAction
{
    public function execute(string $class, int $user_id)
    {
        return Notification::where('notifiable_id', $user_id)
            ->where('type', $class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
