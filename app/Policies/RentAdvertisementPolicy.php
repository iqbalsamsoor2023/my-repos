<?php

namespace App\Policies;

use App\Enums\Residence\Features;
use App\Enums\User\RoleType;
use App\Models\RentAdvertisement;
use App\Models\ResidenceFeature;
use App\Models\User;

class RentAdvertisementPolicy
{
    private $accessibleRoles = ['Super Admin', 'Admin', 'Property Management', 'Property Management Operation Center', 'Re-sales & Tenancy Management'];

    /**
     * Check if user has access to resale and tenancy feature
     */
    private function hasAccess(User $user): bool
    {
        // Check if user has the required roles
        if (! $user->hasAnyRole($this->accessibleRoles)) {
            return false;
        }

        // Additionally check if user is PM and user's residence has resale and tenancy feature enabled
        if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value) && $user->propertyManagement) {
            $resaleFeature = ResidenceFeature::where('residence_id', $user->propertyManagement->id)
                ->where('feature_id', Features::RESALE_AND_TENANCY->value)
                ->where('is_active', 1)
                ->exists();

            return $resaleFeature;
        }

        return true;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RentAdvertisement $rentAdvertisement): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RentAdvertisement $rentAdvertisement): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RentAdvertisement $rentAdvertisement): bool
    {
        return $user->hasAnyRole(['Super Admin']) ? true : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RentAdvertisement $rentAdvertisement): bool
    {
        return $user->hasAnyRole(['Super Admin']) ? true : false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RentAdvertisement $rentAdvertisement): bool
    {
        return $user->hasAnyRole(['Super Admin']) ? true : false;
    }
}
