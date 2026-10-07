<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class ReadSingleNotificationAction
{
    public function execute(string $id)
    {
        $notification = Notification::where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);
    }
}
