<?php

namespace App\Actions\Event;

use App\Enums\UserReaction\ReactionTypeEnum;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;

class GetOneEventAction
{
    public function execute(int $id): Model
    {
        $userId = $request->user_id ?? auth('api')->user()->id;

        $event = Event::with('rsvps', 'userReactions')
            ->withAggregate(['userReactions as user_reaction' => function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId)
                        ->where('reaction_type', '<>', (string) ReactionTypeEnum::READ->value);
                }
            }], 'reaction_type')
            ->withReactionCounts()->findOrFail($id);

        return $event;
    }
}
