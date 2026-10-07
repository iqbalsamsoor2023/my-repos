<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\BillPayeeSetting;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillPayeeSettingPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value];

    private $fullAccessRoles = [RoleType::SUPER_ADMIN->value];

    /**
     * Determine whether the user can view any models.
     *
     * @param User $user
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param BillPayeeSetting $billPayeeSetting
     * @return Response|bool
     */
    public function view(User $user, BillPayeeSetting $billPayeeSetting)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param BillPayeeSetting $billPayeeSetting
     * @return Response|bool
     */
    public function update(User $user, BillPayeeSetting $billPayeeSetting)
    {
        if ($user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ])) {
            return true;
        }

        if ($user->hasAnyRole(RoleType::ADMIN->value)) {
            $demoResidenceIds = Residence::whereIn('residence_activation_status_id', [1, 6])->pluck('id');

            if ($demoResidenceIds->contains($billPayeeSetting->residence_id)) {
                return true;
            }
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param BillPayeeSetting $billPayeeSetting
     * @return Response|bool
     */
    public function delete(User $user, BillPayeeSetting $billPayeeSetting)
    {
        return $user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param BillPayeeSetting $billPayeeSetting
     * @return Response|bool
     */
    public function restore(User $user, BillPayeeSetting $billPayeeSetting)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param BillPayeeSetting $billPayeeSetting
     * @return Response|bool
     */
    public function forceDelete(User $user, BillPayeeSetting $billPayeeSetting)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
