<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\FacilityAndAmenity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FacilityAndAmenityPolicy
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
     * @param FacilityAndAmenity $facilityAndAmenity
     * @return Response|bool
     */
    public function view(User $user, FacilityAndAmenity $facilityAndAmenity)
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
     * @param FacilityAndAmenity $facilityAndAmenity
     * @return Response|bool
     */
    public function update(User $user, FacilityAndAmenity $facilityAndAmenity)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param FacilityAndAmenity $facilityAndAmenity
     * @return Response|bool
     */
    public function delete(User $user, FacilityAndAmenity $facilityAndAmenity)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param FacilityAndAmenity $facilityAndAmenity
     * @return Response|bool
     */
    public function restore(User $user, FacilityAndAmenity $facilityAndAmenity)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param FacilityAndAmenity $facilityAndAmenity
     * @return Response|bool
     */
    public function forceDelete(User $user, FacilityAndAmenity $facilityAndAmenity)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
