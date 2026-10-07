<?php

namespace App\Actions\User;

use App\Exceptions\GeneralException;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeleteUserAction
{
    public function execute(User $user, ?int $actorId = null)
    {
        $user->deleted_by = $actorId;
        $user->save();

        if ($user->delete()) {
            return;
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed deleting user');
    }
}
