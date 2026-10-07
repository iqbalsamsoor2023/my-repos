<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class ReadAllNotificationAction
{
    public function execute(int $user_id)
    {
        $notification = Notification::where('notifiable_id', $user_id)->whereNull('read_at')->update(['read_at' => now()]);
    }
}
