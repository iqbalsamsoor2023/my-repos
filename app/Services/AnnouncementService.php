<?php

namespace App\Services;

use App\Actions\Announcement\CreateAnnouncementAction;
use App\Actions\Announcement\GetAnnouncementAction;
use App\Actions\Announcement\GetOneAnnouncementAction;
use App\Actions\Notification\ReadNotificationAction;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Notification;
use Illuminate\Http\Request;

class AnnouncementService
{
    public function index(Request $request)
    {
        $getAnnouncementAction = new GetAnnouncementAction;
        $announcements = $getAnnouncementAction->execute($request);

        return $announcements;
    }

    public function create(StoreAnnouncementRequest $request)
    {
        $announcement = new CreateAnnouncementAction;

        return $announcement->execute($request);
    }

    public function show(int $id)
    {
        $announcementAction = new GetOneAnnouncementAction;

        return $announcementAction->execute($id);
    }

    public function updateReadStatus(Request $request, int $id)
    {
        $notification = Notification::where('notifiable_id', $request->user_id)
            ->whereJsonContains('data->model_id', $id)
            ->whereNull('read_at')
            ->where('type', 'LIKE', '%Announcement%');

        $readNotificationAction = new ReadNotificationAction;

        if ($notification->count() > 0) {
            $notification = $readNotificationAction->execute($notification);
        }

        return $notification;
    }
}
