<?php

namespace App\Services;

use App\Actions\UserHealth\CreateUserHealthAction;
use App\Actions\UserHealth\UpdateUserHealthAction;
use App\Http\Requests\UserHealth\StoreUserHealthRequest;
use App\Http\Requests\UserHealth\UpdateUserHealthRequest;
use App\Models\UserHealth;

class UserHealthService
{
    public function create(StoreUserHealthRequest $request)
    {
        $userHealthAction = new CreateUserHealthAction;
        $user_health = $userHealthAction->execute($request);

        return $user_health;
    }

    public function update(UpdateUserHealthRequest $request, int $id)
    {
        $user_health = UserHealth::where('user_id', $id)->firstOrFail();

        $userHealthAction = new UpdateUserHealthAction;
        $user_health = $userHealthAction->execute($request, $user_health);

        return $user_health;
    }
}
