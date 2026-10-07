<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value, RoleType::SALES_MANAGEMENT->value, RoleType::RESALES_AND_TENANCY_MANAGEMENT->value];

    private $fullAccessRoles = [RoleType::SUPER_ADMIN->value];

    public static function isGlobalAdmin(?User $user): bool
    {
        return $user?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false;
    }

    public static function isSuperAdmin(?User $user): bool
    {
        return $user?->hasRole(RoleType::SUPER_ADMIN->value) ?? false;
    }

    public static function isPropertyManager(?User $user): bool
    {
        return $user?->hasRole(RoleType::PROPERTY_MANAGEMENT->value) ?? false;
    }

    public static function isOperationCenter(?User $user): bool
    {
        return $user?->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value) ?? false;
    }

    public static function isPropertyManagerOrCenter(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]) ?? false;
    }

    public static function hasPmDashboardAccess(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]) ?? false;
    }

    public static function hasPaymentSettingsAccess(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    public static function isResalesOrSales(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::RESALES_AND_TENANCY_MANAGEMENT->value,
            RoleType::SALES_MANAGEMENT->value,
        ]) ?? false;
    }

    public static function isResalesAndTenancy(?User $user): bool
    {
        return $user?->hasRole(RoleType::RESALES_AND_TENANCY_MANAGEMENT->value) ?? false;
    }

    public static function isSalesManagement(?User $user): bool
    {
        return $user?->hasRole(RoleType::SALES_MANAGEMENT->value) ?? false;
    }

    public function viewAny(User $user)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function view(User $user, Unit $unit)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]);
    }

    public function update(User $user, Unit $unit)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function delete(User $user, Unit $unit)
    {
        return $user->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]);
    }

    public function restore(User $user, Unit $unit)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }

    public function forceDelete(User $user, Unit $unit)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
