<?php

namespace App\Policies;

use App\Enums\User\RoleType;
use App\Models\Erp\CdpCompany;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResidencePolicy
{
    use HandlesAuthorization;

    private const VIEW_ROLES = [
        RoleType::SUPER_ADMIN->value,
        RoleType::ADMIN->value,
        RoleType::PROPERTY_MANAGEMENT->value,
        RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
    ];

    private const CREATE_ROLES = [
        RoleType::SUPER_ADMIN->value,
        RoleType::ADMIN->value,
    ];

    private const UPDATE_ROLES = [
        RoleType::SUPER_ADMIN->value,
        RoleType::ADMIN->value,
        RoleType::PROPERTY_MANAGEMENT->value,
    ];

    private const DELETE_ROLES = [
        RoleType::SUPER_ADMIN->value,
    ];

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Residence $residence): bool
    {
        if (! $user->hasAnyRole(self::VIEW_ROLES)) {
            return false;
        }

        return $this->canAccessResidence($user, $residence);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::CREATE_ROLES);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Residence $residence): bool
    {
        if (! $user->hasAnyRole(self::UPDATE_ROLES)) {
            return false;
        }

        return $this->canAccessResidence($user, $residence);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Residence $residence): bool
    {
        return $user->hasAnyRole(self::DELETE_ROLES);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Residence $residence): bool
    {
        return $user->hasAnyRole(self::DELETE_ROLES);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Residence $residence): bool
    {
        return $user->hasAnyRole(self::DELETE_ROLES);
    }

    private function canAccessResidence(User $user, Residence $residence): bool
    {
        if ($user->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return true;
        }

        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            return (int) $residence->property_management_user_id === (int) $user->id;
        }

        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value)) {
            $companyId = CdpCompany::query()
                ->where('mmb_user_id', $user->id)
                ->value('id');

            return $companyId !== null
                && (int) $residence->property_management_id === (int) $companyId;
        }

        return true;
    }
}
