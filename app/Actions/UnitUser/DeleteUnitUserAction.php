<?php

namespace App\Actions\UnitUser;

use App\Exceptions\GeneralException;
use App\Models\UnitUser;
use Illuminate\Http\JsonResponse;

class DeleteUnitUserAction
{
    public function execute(UnitUser $unit_user)
    {
        if ($unit_user->delete()) {
            return;
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed deleting unit user');
    }
}
