<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\Residence;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportTicketPolicy
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
     * @param SupportTicket $supportTicket
     * @return Response|bool
     */
    public function view(User $user, SupportTicket $supportTicket)
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
            RoleType::PROPERTY_MANAGEMENT->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param SupportTicket $supportTicket
     * @return Response|bool
     */
    public function update(User $user, SupportTicket $supportTicket)
    {
        if ($user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ])) {
            return true;
        }

        if ($user->hasAnyRole(RoleType::ADMIN->value)) {
            $demoResidenceIds = Residence::whereIn('residence_activation_status_id', [1, 6])->pluck('id');

            if ($demoResidenceIds->contains($supportTicket?->unit?->residence_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param SupportTicket $supportTicket
     * @return Response|bool
     */
    public function delete(User $user, SupportTicket $supportTicket)
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
     * @param SupportTicket $supportTicket
     * @return Response|bool
     */
    public function restore(User $user, SupportTicket $supportTicket)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param SupportTicket $supportTicket
     * @return Response|bool
     */
    public function forceDelete(User $user, SupportTicket $supportTicket)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
