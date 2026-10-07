<?php

namespace App\Listeners;

use App\Events\MaintenanceCreated;
use App\Helpers\MaintenanceClaimNoGenerator;

class GenerateMaintenanceClaimNo
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
     * @return void
     */
    public function handle(MaintenanceCreated $event)
    {
        $maintenance = $event->maintenance;
        if ($maintenance->maintainable_type == 'App\Models\Unit') {
            $maintenance->maintainable_claim_number = MaintenanceClaimNoGenerator::generate($maintenance);
            $maintenance->save();
        } else {
            $maintenance->maintainable_claim_number = MaintenanceClaimNoGenerator::generatePublicClaimNo($maintenance);
            $maintenance->save();
        }
    }
}
