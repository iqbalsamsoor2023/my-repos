<?php

namespace App\Services;

use App\Actions\Unit\CheckInvitationCodeValidityAction;
use App\Actions\Unit\GetOneUnitAction;
use App\Actions\Unit\ListUnitAction;
use App\Http\Requests\GetUnitRequest;
use App\Http\Requests\Unit\InvitationCodeValidityRequest;

class UnitService
{
    public function index(GetUnitRequest $request)
    {
        $listUnitAction = new ListUnitAction;
        $unit = $listUnitAction->execute($request);

        return $unit;
    }

    public function show(int $id)
    {
        $unitAction = new GetOneUnitAction;
        $unit = $unitAction->execute($id);

        return $unit;
    }

    public function invitationCodeValidity(InvitationCodeValidityRequest $request)
    {
        $checkInvitationCodeAction = new CheckInvitationCodeValidityAction;
        $unit = $checkInvitationCodeAction->execute($request);

        return $unit;
    }
}
