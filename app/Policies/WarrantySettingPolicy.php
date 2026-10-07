<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\WarrantySetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WarrantySettingPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value, RoleType::PROPERTY_MANAGEMENT];

    private $fullAccessRoles = [RoleType::SUPER_ADMIN->value];

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, WarrantySetting $warrantySetting)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, WarrantySetting $warrantySetting)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, WarrantySetting $warrantySetting)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, WarrantySetting $warrantySetting)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
