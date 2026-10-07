<?php

namespace App\Actions\UnitUser;

use App\Enums\Unit\InvitationType;
use App\Enums\UnitUser\ApprovalStatusType;
use App\Enums\UnitUser\UnitUserRoleType;
use App\Enums\User\RoleType;
use App\Exceptions\GeneralException;
use App\Models\UnitUser;
use Illuminate\Http\JsonResponse;

class CreateUnitUserAction
{
    public function execute($request, int $user_id)
    {
        if (($request->invitation_type ?? null) == InvitationType::OWNER->value || ($request->role ?? null) == RoleType::UNIT_OWNER->value) {
            $request->is_owner = UnitUserRoleType::OWNER->value;
        }

        $request->merge([
            'is_owner' => isset($request->is_owner) ? $request->is_owner : UnitUserRoleType::TENANT->value,
            'user_id' => $user_id,
            'approval_status' => ApprovalStatusType::APPROVED->value,
            'is_created_via_family' => $request->created_via_family ?? false,
        ]);

        $unit_user = UnitUser::create($request->only([
            'unit_id',
            'user_id',
            'insurance_company_id',
            'relationship',
            'is_owner',
            'mmb_id',
            'approval_status',
            'is_created_via_family',
        ]));

        if (! $unit_user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating unit user');
        }

        return $unit_user;
    }
}
