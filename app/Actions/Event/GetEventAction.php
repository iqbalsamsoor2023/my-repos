<?php

namespace App\Actions\Event;

use App\Enums\UserReaction\ReactionTypeEnum;
use App\Models\Event;

class GetEventAction
{
    public function execute($request)
    {
        $userId = $request->user_id ?? auth('api')->user()->id;

        $event = Event::with('rsvps', 'modelHasRole')
            ->withAggregate([
                'userReactions as user_reaction' => function ($query) use ($userId) {
                    if ($userId) {
                        $query->where('user_id', $userId)
                            ->where('reaction_type', '<>', (string) ReactionTypeEnum::READ->value);
                    }
                },
            ], 'reaction_type')
            ->withReactionCounts()
            ->where('is_active', true);

        if (isset($request->residence_id)) {
            $event = $event->where('residence_id', $request->residence_id);
        }

        if (isset($request->role_id)) {
            $event = $event->whereHas('modelHasRole', function ($query) use ($request) {
                $query->where('role_id', $request->role_id);
            });
        }

        return $event->orderBy('id', 'desc')->paginate(25);
    }
}
