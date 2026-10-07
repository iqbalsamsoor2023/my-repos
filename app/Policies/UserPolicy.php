<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [
        RoleType::SUPER_ADMIN->value,
        RoleType::ADMIN->value,
        RoleType::DEVELOPER->value,
        RoleType::PROPERTY_MANAGEMENT->value,
        RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        RoleType::RESALES_AND_TENANCY_MANAGEMENT->value,
        RoleType::SALES_MANAGEMENT->value,
    ];

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
     * @param User $model
     * @return Response|bool
     */
    public function view(User $user, User $model)
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
            RoleType::ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param User $model
     * @return Response|bool
     */
    public function update(User $user, User $model)
    {
        // Full access roles (Super Admin) can edit anyone
        if ($user->hasAnyRole($this->fullAccessRoles)) {
            return true;
        }

        // Admin can edit other users except full access roles
        if ($user->hasRole(RoleType::ADMIN->value)) {
            return ! $model->hasAnyRole($this->fullAccessRoles);
        }

        // Other accessible roles can edit only themselves
        if ($user->hasAnyRole($this->accessibleRoles)) {
            return $user->id === $model->id;
        }

        // Everyone else can edit only themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param User $model
     * @return Response|bool
     */
    public function delete(User $user, User $model)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param User $model
     * @return Response|bool
     */
    public function restore(User $user, User $model)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param User $model
     * @return Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
