<?php

namespace App\Actions\Announcement;

use App\Enums\UserReaction\ReactionTypeEnum;
use App\Models\Announcement;

class GetOneAnnouncementAction
{
    public function execute(int $id): ?Announcement
    {
        $userId = $request->user_id ?? auth('api')->user()->id;

        $announcement = Announcement::withAggregate(['userReactions as user_reaction' => function ($query) use ($userId) {
            if ($userId) {
                $query->where('user_id', $userId)
                    ->where('reaction_type', '<>', (string) ReactionTypeEnum::READ->value);
            }
        }], 'reaction_type')
            ->withReactionCounts()
            ->find($id);

        return $announcement;
    }
}
