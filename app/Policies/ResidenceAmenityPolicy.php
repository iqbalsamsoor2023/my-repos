<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ResidenceAmenity;
use App\Enums\User\RoleType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResidenceAmenityPolicy
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
     * @param ResidenceAmenity $residenceAmenity
     * @return Response|bool
     */
    public function view(User $user, ResidenceAmenity $residenceAmenity)
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
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param ResidenceAmenity $residenceAmenity
     * @return Response|bool
     */
    public function update(User $user, ResidenceAmenity $residenceAmenity)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param ResidenceAmenity $residenceAmenity
     * @return Response|bool
     */
    public function delete(User $user, ResidenceAmenity $residenceAmenity)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param ResidenceAmenity $residenceAmenity
     * @return Response|bool
     */
    public function restore(User $user, ResidenceAmenity $residenceAmenity)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param ResidenceAmenity $residenceAmenity
     * @return Response|bool
     */
    public function forceDelete(User $user, ResidenceAmenity $residenceAmenity)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
