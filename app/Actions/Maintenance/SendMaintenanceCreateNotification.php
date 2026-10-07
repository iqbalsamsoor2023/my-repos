<?php

namespace App\Actions\Maintenance;

use App\Models\Maintenance;
use App\Notifications\MaintenanceCreated;
use Illuminate\Support\Facades\Notification;

class SendMaintenanceCreateNotification extends BaseMaintenanceNotification
{
    public function execute(Maintenance $maintenance)
    {
        $unitUsers = $this->getUnitUsers($maintenance);
        $residenceId = $this->getResidenceId($maintenance);

        if ($unitUsers->isNotEmpty()) {
            // Send notification to all users
            $this->sendNotificationToUsers($unitUsers, MaintenanceCreated::class, $maintenance);

            // Send notification to PM
            if ($residenceId) {
                $this->sendNotificationToPM($residenceId, $maintenance, MaintenanceCreated::class);
            }
        }
    }
}
