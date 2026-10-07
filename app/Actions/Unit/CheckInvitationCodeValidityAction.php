<?php

namespace App\Actions\Unit;

use App\Exceptions\GeneralException;
use App\Http\Requests\Unit\InvitationCodeValidityRequest;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;

class CheckInvitationCodeValidityAction
{
    public function execute(InvitationCodeValidityRequest $request)
    {
        $unit = Unit::with(['residence', 'residence.subdistrict', 'residence.subdistrict.district', 'residence.subdistrict.district.province'])
            ->where('invitation_code_owner', $request->invitation_code)->orWhere('invitation_code_tenant', $request->invitation_code)->first();

        if (! $unit) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Invalid invitation code');
        }

        return $unit;
    }
}
