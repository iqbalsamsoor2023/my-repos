<?php

namespace App\Actions\User;

use App\Enums\Unit\InvitationType;
use App\Enums\User\RoleType;
use App\Exceptions\GeneralException;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function execute($request)
    {
        $request->merge([
            // 'email_verified_at' => now(),
            'password' => Hash::make($request->password),
        ]);

        $platform = $request->platform ?? 'N/A';
        $appVersionCode = $request->app_version_code ?? 0;

        if ($request->has('app_version_code')) {
            $request->merge([
                'app_version_code' => $request->app_version_code,
            ]);
        }

        $user = User::create($request->only([
            'country_id',
            'name',
            'email',
            'id_number',
            'password',
            'phone_no',
            'address',
            'date_of_birth',
            'gender',
            'passport_number',
            'passport_expiry',
            'email_verified_at',
            'pdpa_agreed_at',
            $platform,
            $appVersionCode,
        ]));

        if (! $user) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating user');
        }

        $this->uploadProfileImage($user, $request);
        $this->assignRole($user, $request);

        return $user;
    }

    private function uploadProfileImage(User $user, $request)
    {
        if ($request->hasFile('image')) {
            $user->addMediaFromRequest('image')->toMediaCollection();
        }
    }

    private function assignRole(User $user, $request)
    {
        if ($request->invitation_type == InvitationType::OWNER->value || $request->role == RoleType::UNIT_OWNER->value) {
            $user->assignRole(RoleType::UNIT_OWNER->value);
        } elseif ($request->invitation_type == InvitationType::TENANT->value || $request->role == RoleType::UNIT_TENANT->value) {
            $user->assignRole(RoleType::UNIT_TENANT->value);
        } else {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed assigning role to the user');
        }
    }
}
