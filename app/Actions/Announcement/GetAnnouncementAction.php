<?php

namespace App\Actions\Announcement;

use App\Enums\UserReaction\ReactionTypeEnum;
use App\Models\Announcement;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GetAnnouncementAction
{
    public function execute(Request $request)
    {
        $userId = $request->recipient_id ?? auth('api')->user()->id;

        $announcements = Announcement::where('is_active', true)
            ->with([
                'residence',
                'modelHasRole',
                'media',
            ])
            ->withAggregate([
                'userReactions as user_reaction' => function ($query) use ($userId) {
                    if ($userId) {
                        $query->where('user_id', $userId)
                            ->where('reaction_type', '<>', (string) ReactionTypeEnum::READ->value);
                    }
                },
            ], 'reaction_type')
            ->withReactionCounts();

        // Filters
        if (isset($request->role_id)) {
            $announcements->whereHas('modelHasRole', function ($query) use ($request) {
                $query->where('role_id', $request->role_id);
            });
        }

        if (isset($request->residence_id)) {
            $announcements->where('residence_id', $request->residence_id);
        }

        if (isset($request->created_by)) {
            $announcements->where('created_by', $request->created_by);
        }

        if (isset($request->unit_id)) {
            $unitId = $request->unit_id;

            $announcements->where(function ($query) use ($unitId) {
                $query->whereDoesntHave('announcementUnits')
                    ->orWhereHas('announcementUnits', function ($subQuery) use ($unitId) {
                        $subQuery->where('unit_id', $unitId);
                    });
            });
        }

        $announcements = $announcements->orderBy('id','DESC')->paginate(20);

        // Preload notifications to avoid N+1
        if ($userId) {
            $announcementIds = $announcements->pluck('id');
            $notifications = Notification::where('notifiable_id', $userId)
                ->where('type', 'App\Notifications\AnnouncementCreated')
                ->whereIn('data->model_id', $announcementIds)
                ->whereNull('read_at')
                ->get()
                ->keyBy(fn($n) => $n->data['model_id']);
        } else {
            $notifications = collect();
        }

        $twoWeeksAgo = Carbon::now()->subWeeks(2);

        // Attach read_at_indicator
        $announcements->getCollection()->transform(function ($announcement) use ($notifications, $twoWeeksAgo) {
            // If older than 2 weeks, auto-consider read
            if ($announcement->created_at < $twoWeeksAgo) {
                $announcement->read_at_indicator = true;
            } else {
                $announcement->read_at_indicator = $notifications->has($announcement->id);
            }
            return $announcement;
        });

        return $announcements;
    }
}