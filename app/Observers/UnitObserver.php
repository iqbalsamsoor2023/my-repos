<?php

namespace App\Observers;

use App\Jobs\SyncUnitStatsViewJob;
use App\Jobs\SyncUnitUserStatsViewJob;
use App\Models\Unit;

class UnitObserver
{
    public function saved(Unit $unit): void
    {
        SyncUnitStatsViewJob::dispatch($unit->residence_id)->afterCommit();
        SyncUnitUserStatsViewJob::dispatch($unit->residence_id)->afterCommit();
    }

    public function deleted(Unit $unit): void
    {
        SyncUnitStatsViewJob::dispatch($unit->residence_id)->afterCommit();
        SyncUnitUserStatsViewJob::dispatch($unit->residence_id)->afterCommit();
    }

    public function restored(Unit $unit): void
    {
        SyncUnitStatsViewJob::dispatch($unit->residence_id)->afterCommit();
        SyncUnitUserStatsViewJob::dispatch($unit->residence_id)->afterCommit();
    }

    public function forceDeleted(Unit $unit): void
    {
        SyncUnitStatsViewJob::dispatch($unit->residence_id)->afterCommit();
        SyncUnitUserStatsViewJob::dispatch($unit->residence_id)->afterCommit();
    }
}
