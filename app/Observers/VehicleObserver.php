<?php

namespace App\Observers;

use App\Jobs\SyncVehicleStatsViewJob;
use App\Models\Vehicle;

class VehicleObserver
{
    public function saved(Vehicle $vehicle): void
    {
        SyncVehicleStatsViewJob::dispatch($vehicle->id)->afterCommit();
    }

    public function deleted(Vehicle $vehicle): void
    {
        SyncVehicleStatsViewJob::dispatch($vehicle->id)->afterCommit();
    }

    public function restored(Vehicle $vehicle): void
    {
        SyncVehicleStatsViewJob::dispatch($vehicle->id)->afterCommit();
    }

    public function forceDeleted(Vehicle $vehicle): void
    {
        SyncVehicleStatsViewJob::dispatch($vehicle->id)->afterCommit();
    }
}
