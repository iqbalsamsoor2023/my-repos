<?php

namespace App\Helpers\Residence;

use App\Actions\UserSgoc\CreateSecurityGuardUserAction;
use App\Enums\User\RoleType;
use App\Helpers\AutomationAccountGenerator;
use App\Models\Residence;
use App\Models\User;

class AccountHelper
{
    public static function manageAccountCreation(Residence $residence, $roleType, string $roleName): void
    {
        if ($roleType == RoleType::SC->value) {
            $securityGuardUserAction = new CreateSecurityGuardUserAction;
            $securityGuard = $securityGuardUserAction->execute($residence);
            self::manageResidenceGuard($residence, $securityGuard);
        } else {
            $generateAccount = AutomationAccountGenerator::generate($residence, $roleType);
            $accountCreation = User::create(AutomationAccountGenerator::manageAccount($generateAccount));
            AutomationAccountGenerator::attachRole($accountCreation, $roleName);
            self::manageAccount($residence, $accountCreation, $roleName);
        }
    }

    public static function manageAccount(Residence $residence, User $user, string $role): void
    {
        $roleMappings = [
            RoleType::SALES_MANAGEMENT->value => 'sales_management_user_id',
            RoleType::RECEPTIONIST->value => 'receptionist_user_id',
            RoleType::FACILITIES_MANAGEMENT->value => 'technician_user_id',
            RoleType::PROPERTY_MANAGEMENT->value => 'property_management_user_id',
            RoleType::RESALES_AND_TENANCY_MANAGEMENT->value => 'rtm_user_id',
            RoleType::ACCOUNTANT->value => 'accountant_user_id',
        ];

        if (isset($roleMappings[$role])) {
            $residence->{$roleMappings[$role]} = $user->id;
            $residence->save();
        }
    }

    public static function manageResidenceGuard(Residence $residence, $sgoc_user): void
    {
        $residence->sgoc_residence_guard_user_id = $sgoc_user['data']['id'];
        $residence->save();
    }
}
