<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Enums\User\RoleType;
use App\Models\UnitUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitUserPolicy
{
    use HandlesAuthorization;

    private $accessibleRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value];

    private $fullAccessRoles = [RoleType::SUPER_ADMIN->value];

    private $restrictAccessRoles = [RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value];

    public static function isGlobalAdmin(?User $user): bool
    {
        return $user?->hasAnyRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]) ?? false;
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

    public static function hasDashboardAccess(?User $user): bool
    {
        return $user?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
        ]) ?? false;
    }

    public function viewAny(User $user)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function view(User $user, UnitUser $unitUser)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function update(User $user, UnitUser $unitUser)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function delete(User $user, UnitUser $unitUser)
    {
        return $user->hasAnyRole($this->accessibleRoles);
    }

    public function restore(User $user, UnitUser $unitUser)
    {
        return $user->hasAnyRole($this->restrictAccessRoles);
    }

    public function forceDelete(User $user, UnitUser $unitUser)
    {
        return $user->hasAnyRole($this->fullAccessRoles);
    }
}
