<?php

namespace App\Actions\User;

use App\Exceptions\GeneralException;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class GetUserAction
{
    public function execute($request)
    {
        $user = User::when($request->has('email'), function ($query) use ($request) {
            $query->where('email', $request->email);
        })
            ->when($request->has('user_id'), function ($query) use ($request) {
                $query->where('id', $request->user_id);
            })
            ->first();

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'User not found');
        }

        return $user;
    }
}
