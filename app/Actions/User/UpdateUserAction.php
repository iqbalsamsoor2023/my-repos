<?php

namespace App\Actions\User;

use App\Exceptions\GeneralException;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UpdateUserAction
{
    public function execute(UpdateUserRequest $request, User $user)
    {
        $request->merge([
            'passport_number' => $request->country_id == 1 ? null : $request->passport_number,
            'passport_expiry' => $request->country_id == 1 ? null : $request->passport_expiry,
        ]);

        $user->update($request->only([
            'name',
            'country_id',
            'email',
            'id_number',
            'phone_no',
            'date_of_birth',
            'gender',
            'passport_number',
            'passport_expiry',
            'pdpa_agreed_at',
        ]));

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating user');
        }

        return $user;
    }
}
