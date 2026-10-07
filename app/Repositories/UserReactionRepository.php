<?php

namespace App\Repositories;

use App\Actions\UserReaction\StoreUserReactionAction;
use App\Http\Requests\UserReaction\UserReactionRequest;

class UserReactionRepository
{
    public function store(UserReactionRequest $request)
    {
        $storeUserReactionAction = new StoreUserReactionAction;
        $userReaction = $storeUserReactionAction->execute($request);

        return $userReaction;
    }
}
