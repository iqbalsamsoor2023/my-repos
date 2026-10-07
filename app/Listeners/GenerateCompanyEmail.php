<?php

namespace App\Listeners;

use App\Enums\User\RoleType;
use App\Events\CompanyCreated;
use App\Helpers\AutomationAccountGenerator;
use App\Models\User;

class GenerateCompanyEmail
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
     * @param CompanyCreated $event
     * @return void
     */
    public function handle(CompanyCreated $event)
    {
        $company = $event->company;

        if ($company->type == 'Property Management') {
            $generate_pmoc = AutomationAccountGenerator::generatePmoc($company, RoleType::PMOC->value);
            $acc_pmoc = User::create(AutomationAccountGenerator::manageAccount($generate_pmoc));
            AutomationAccountGenerator::attachRole($acc_pmoc, RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value);
            $this->manageCompanyManagement($company, $acc_pmoc);
        }
    }

    private function manageCompanyManagement($company, User $user): void
    {
        $company->user_id = $user->id;
        $company->save();
    }
}
