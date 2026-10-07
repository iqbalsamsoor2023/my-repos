<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\User;
use App\Models\UserHealth;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserHealthPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value];

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
     * @param UserHealth $userHealth
     * @return Response|bool
     */
    public function view(User $user, UserHealth $userHealth)
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
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param UserHealth $userHealth
     * @return Response|bool
     */
    public function update(User $user, UserHealth $userHealth)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param UserHealth $userHealth
     * @return Response|bool
     */
    public function delete(User $user, UserHealth $userHealth)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param UserHealth $userHealth
     * @return Response|bool
     */
    public function restore(User $user, UserHealth $userHealth)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param UserHealth $userHealth
     * @return Response|bool
     */
    public function forceDelete(User $user, UserHealth $userHealth)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
