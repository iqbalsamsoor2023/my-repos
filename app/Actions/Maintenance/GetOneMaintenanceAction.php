<?php

namespace App\Actions\Maintenance;

use App\Models\Maintenance;

class GetOneMaintenanceAction
{
    public function execute(int $id)
    {
        return Maintenance::with(
            'reportedBy',
            'maintainable',
            'maintenanceProgressions'
        )
            ->findOrFail($id);
    }
}
