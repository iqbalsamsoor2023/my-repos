<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;

class CommitteePolicy
{
    /**
     * Roles allowed to manage committees.
     */
    protected array $allowedRoles = ['Super Admin', 'Admin', 'Property Management'];

    /**
     * Check if the user has permission to manage committees.
     */
    protected function hasPermission(User $user): bool
    {
        return $user->hasAnyRole($this->allowedRoles);
    }

    /**
     * Determine whether the user can view any committees.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can view the committee.
     */
    public function view(User $user, Committee $committee): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can create committees.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can update the committee.
     */
    public function update(User $user, Committee $committee): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can delete the committee.
     */
    public function delete(User $user, Committee $committee): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can restore the committee.
     */
    public function restore(User $user, Committee $committee): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can permanently delete the committee.
     */
    public function forceDelete(User $user, Committee $committee): bool
    {
        return $this->hasPermission($user);
    }
}
