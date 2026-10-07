<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceStatus;
use App\Enums\Maintenance\MaintenanceVerificationStatus;
use App\Models\Maintenance;
use App\Notifications\MaintenanceUpdated;
use App\Notifications\MaintenanceVerificationUpdated;

class SendMaintenanceUpdateNotification extends BaseMaintenanceNotification
{
    public function execute(Maintenance $maintenance)
    {
        // if status is update to in progress or complete
        if ($maintenance->is_verified == null && (in_array($maintenance->status, [MaintenanceStatus::IN_PROGRESS->value, MaintenanceStatus::COMPLETE->value]))) {
            $this->sendStatusNotification($maintenance);
        } elseif (($maintenance->status == MaintenanceStatus::COMPLETE->value && ($maintenance->is_verified == MaintenanceVerificationStatus::VERIFIED->label())) || ($maintenance->status == MaintenanceStatus::IN_PROGRESS->value && $maintenance->is_verified == MaintenanceVerificationStatus::NOT_VERIFIED->label())) { // if status is verify
            $this->sendVerificationStatusNotification($maintenance);
        }
    }

    private function sendStatusNotification($maintenance)
    {
        $unitUsers = $this->getUnitUsers($maintenance);
        $this->sendNotificationToUsers($unitUsers, MaintenanceUpdated::class, $maintenance);
    }

    private function sendVerificationStatusNotification($maintenance)
    {
        $unitUsers = $this->getUnitUsers($maintenance);
        $this->sendNotificationToUsers($unitUsers, MaintenanceVerificationUpdated::class, $maintenance);
    }
}
