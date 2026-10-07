<?php

namespace App\Observers;

use App\Jobs\SyncUnitStatsViewJob;
use App\Jobs\SyncUnitUserStatsViewJob;
use App\Models\UnitUser;
use Illuminate\Support\Facades\Cache;

class UnitUserObserver
{
    public function saved(UnitUser $unitUser): void
    {
        if ($unitUser->unit) {
            Cache::tags(["units:residence:{$unitUser->unit->residence_id}"])->flush();
            SyncUnitStatsViewJob::dispatch($unitUser->unit->residence_id)->afterCommit();
            SyncUnitUserStatsViewJob::dispatch($unitUser->unit->residence_id)->afterCommit();
        }
    }

    public function deleted(UnitUser $unitUser): void
    {
        if ($unitUser->unit) {
            Cache::tags(["units:residence:{$unitUser->unit->residence_id}"])->flush();
            SyncUnitStatsViewJob::dispatch($unitUser->unit->residence_id)->afterCommit();
            SyncUnitUserStatsViewJob::dispatch($unitUser->unit->residence_id)->afterCommit();
        }
    }
}
