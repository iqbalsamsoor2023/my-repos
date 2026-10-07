<?php

namespace App\Actions\User;

use App\Http\Requests\User\UpdateProfilePictureRequest;
use App\Models\User;

class UpdateProfilePictureAction
{
    public function execute(UpdateProfilePictureRequest $request, User $user)
    {
        if ($request->hasFile('image')) {
            $user->clearMediaCollection();
            $user->addMediaFromRequest('image')->toMediaCollection();
        }
    }
}
