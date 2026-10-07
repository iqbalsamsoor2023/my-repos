<?php

namespace App\Listeners;

use App\Actions\UserSgoc\CreateSecurityGuardUserAction;
use App\Enums\User\RoleType;
use App\Events\ResidenceCreated;
use App\Helpers\AutomationAccountGenerator;
use App\Models\User;

class GenerateEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ResidenceCreated $event
     * @return void
     */
    public function handle(ResidenceCreated $event)
    {
        $residence = $event->residence;

        // create property management account
        $generate_property_management = AutomationAccountGenerator::generate($residence, RoleType::PM->value);
        $acc_property_management = User::create(AutomationAccountGenerator::manageAccount($generate_property_management));
        AutomationAccountGenerator::attachRole($acc_property_management, RoleType::PROPERTY_MANAGEMENT->value);
        $this->manageResidenceManagement($residence, $acc_property_management);

        // create sgoc residence guard account
        $securityGuardUserAction = new CreateSecurityGuardUserAction($residence);
        $securityGuard = $securityGuardUserAction->execute($residence);
        $this->manageResidenceGuard($residence, $securityGuard);

        // create accountant account
        $generate_accountant = AutomationAccountGenerator::generate($residence, RoleType::AC->value);
        $acc_accountant = User::create(AutomationAccountGenerator::manageAccount($generate_accountant));
        AutomationAccountGenerator::attachRole($acc_accountant, RoleType::ACCOUNTANT->value);
        $this->manageAccountant($residence, $acc_accountant);
    }

    private function manageResidenceManagement($residence, User $user): void
    {
        $residence->property_management_user_id = $user->id;
        $residence->save();
    }

    private function manageResidenceGuard($residence, $sgoc_user): void
    {
        $residence->sgoc_residence_guard_user_id = $sgoc_user['data']['id'];
        $residence->save();
    }

    private function manageAccountant($residence, User $user): void
    {
        $residence->accountant_user_id = $user->id;
        $residence->save();
    }
}
