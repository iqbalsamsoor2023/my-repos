<?php

namespace App\Services;

use App\Actions\UnitUser\CreateUnitUserAction;
use App\Actions\UnitUser\GetUnitUserAction;
use App\Exceptions\GeneralException;
use App\Helpers\MmbIdGenerator;
use App\Http\Requests\UnitUser\StoreUnitUserRequest;
use App\Models\UnitUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitUserService
{
    public function index(Request $request)
    {
        $getUnitUserAction = new GetUnitUserAction;
        $unitUser = $getUnitUserAction->execute($request);

        return $unitUser;
    }

    public function create(StoreUnitUserRequest $request)
    {
        // Check if the user is already assigned to the unit
        $exists = UnitUser::where('unit_id', $request->unit_id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            throw new GeneralException(
                JsonResponse::HTTP_BAD_REQUEST,
                'User is already assigned to this unit.'
            );
        }

        // Create the unit-user relationship
        $unitUser = (new CreateUnitUserAction)->execute($request, $request->user_id);

        // Attempt to generate MMB ID if unit exists
        if ($unitUser->unit) {
            MmbIdGenerator::execute($unitUser->unit);
        } else {
            logger()->warning('MMB ID generation skipped: Unit not found for UnitUser ID '.$unitUser->id);
        }

        return $unitUser;
    }
}
