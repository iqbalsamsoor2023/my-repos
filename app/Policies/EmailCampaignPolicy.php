<?php

namespace App\Policies;

use App\Models\EmailCampaign;
use App\Models\User;

class EmailCampaignPolicy
{
    protected array $allowedRoles = ['Super Admin', 'Admin'];

    /**
     * Check if the user has permission to manage email campaigns.
     */
    protected function hasPermission(User $user): bool
    {
        return $user->hasAnyRole($this->allowedRoles);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->hasPermission($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->hasPermission($user);
    }
}
