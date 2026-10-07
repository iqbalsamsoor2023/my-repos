<?php

namespace App\Policies;

use App\Enums\Residence\ActivationStatusType;
use App\Enums\User\RoleType;
use App\Models\Parcel;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Collection;

class ParcelPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value];

    private $fullAccessRoles = [RoleType::SUPER_ADMIN->value];

    public static function isGlobalAdmin(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
        ]) ?? false;
    }

    public static function isPropertyManager(?User $user): bool
    {
        return $user?->hasRole(RoleType::PROPERTY_MANAGEMENT->value) ?? false;
    }

    public static function isOperationCenter(?User $user): bool
    {
        return $user?->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value) ?? false;
    }

    public static function hasDashboardAccess(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]) ?? false;
    }

    /**
     * Cached demo residence IDs, shared across every policy instance.
     *
     * The Gate resolves a fresh policy instance for each authorization check
     * (e.g. once per table row), so this is stored statically to ensure the
     * underlying query runs only once per request instead of per record.
     *
     * @var Collection<int, int>|null
     */
    private static ?Collection $demoResidenceIds = null;

    /**
     * IDs of residences considered "demo" (admins may edit their parcels).
     *
     * @return Collection<int, int>
     */
    private function demoResidenceIds(): Collection
    {
        return static::$demoResidenceIds ??= Residence::query()
            ->whereIn('residence_activation_status_id', [
                ActivationStatusType::INACTIVE_DEMO->value,
                ActivationStatusType::ACTIVE_DEMO->value,
            ])
            ->pluck('id');
    }

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
    public function view(User $user, Parcel $parcel)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, Parcel $parcel)
    {
        if ($user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ])) {
            return true;
        }

        if ($user->hasAnyRole(RoleType::ADMIN->value)) {
            if ($this->demoResidenceIds()->contains($parcel?->unit?->residence_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, Parcel $parcel)
    {
        return $user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, Parcel $parcel)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, Parcel $parcel)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
