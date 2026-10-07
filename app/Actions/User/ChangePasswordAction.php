<?php

namespace App\Actions\User;

use App\Exceptions\GeneralException;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ChangePasswordAction
{
    public function execute(ChangePasswordRequest $request, User $user)
    {
        $user = $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed changing password');
        }

        return $user;
    }
}
