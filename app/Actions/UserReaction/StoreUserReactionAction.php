<?php

namespace App\Actions\UserReaction;

use App\Enums\UserReaction\ReactableTypeEnum;
use App\Enums\UserReaction\ReactionTypeEnum;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class StoreUserReactionAction
{
    public function execute(Request $request)
    {
        if ($request->reactable_type == ReactableTypeEnum::ANNOUNCEMENT->value) {
            $reactableItem = Announcement::findOrFail($request->reactable_id);
        } elseif ($request->reactable_type == ReactableTypeEnum::EVENT->value) {
            $reactableItem = Event::findOrFail($request->reactable_id);
        }

        // when removing reaction, delete only the like reaction for the specific item
        if ($request->reaction_type == ReactionTypeEnum::REMOVE_REACTION->value) {
            $user = User::find($request->user_id);
            $reactableItem->userReactions()
                ->where('user_id', $request->user_id)
                ->where('reactable_id', $request->reactable_id)
                ->where('reaction_type', (string) ReactionTypeEnum::LIKE->value)
                ->delete();

            return;
        }

        // using firstOrCreate, so that calling API twice wont duplicate the reaction record
        $reactableItem->userReactions()->firstOrCreate($request->only(['reactable_id', 'user_id', 'reaction_type']));
    }
}
