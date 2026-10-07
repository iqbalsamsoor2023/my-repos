<?php

namespace App\Actions\UnitUser;

use App\Exceptions\GeneralException;
use App\Models\UnitUser;
use Illuminate\Http\JsonResponse;

class UpdateUnitUserAction
{
    public function execute($request, UnitUser $unit_user)
    {
        $unit_user->update($request->all());

        if (! $unit_user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating unit user');
        }

        return $unit_user;
    }
}
