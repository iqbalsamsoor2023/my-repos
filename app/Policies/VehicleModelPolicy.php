<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\User;
use App\Models\VehicleModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleModelPolicy
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
     * @param VehicleModel $vehicleModel
     * @return Response|bool
     */
    public function view(User $user, VehicleModel $vehicleModel)
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
     * @param VehicleModel $vehicleModel
     * @return Response|bool
     */
    public function update(User $user, VehicleModel $vehicleModel)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param VehicleModel $vehicleModel
     * @return Response|bool
     */
    public function delete(User $user, VehicleModel $vehicleModel)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param VehicleModel $vehicleModel
     * @return Response|bool
     */
    public function restore(User $user, VehicleModel $vehicleModel)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param VehicleModel $vehicleModel
     * @return Response|bool
     */
    public function forceDelete(User $user, VehicleModel $vehicleModel)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
