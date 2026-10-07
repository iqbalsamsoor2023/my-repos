<?php

namespace App\Observers;

use App\Jobs\SyncResidenceStatsViewJob;
use App\Jobs\SyncUnitUserStatsViewJob;
use App\Models\Residence;
use App\Models\ResidenceStatsView;
use Illuminate\Support\Facades\DB;

class ResidenceObserver
{
    /**
     * Handle the Residence "created" event.
     */
    public function created(Residence $residence): void
    {
        SyncResidenceStatsViewJob::dispatch($residence->id);
        SyncUnitUserStatsViewJob::dispatch($residence->id)->afterCommit();
    }

    /**
     * Handle the Residence "updated" event.
     */
    public function updated(Residence $residence): void
    {
        // Sync PVR site if lat/long changed
        if ($residence->wasChanged(['latitude', 'longitude'])) {
            $residence->pvrSite()->update([
                'latitude' => $residence->latitude,
                'longitude' => $residence->longitude,
            ]);
        }

        // Always sync read model on update
        SyncResidenceStatsViewJob::dispatch($residence->id);
        SyncUnitUserStatsViewJob::dispatch($residence->id)->afterCommit();
    }

    /**
     * Handle the Residence "deleted" event.
     */
    public function deleted(Residence $residence): void
    {
        ResidenceStatsView::where('residence_id', $residence->id)->delete();
        DB::table('unit_user_stats_view')->where('residence_id', $residence->id)->delete();
    }

    /**
     * Handle the Residence "restored" event.
     */
    public function restored(Residence $residence): void
    {
        SyncResidenceStatsViewJob::dispatch($residence->id);
        SyncUnitUserStatsViewJob::dispatch($residence->id)->afterCommit();
    }

    /**
     * Handle the Residence "force deleted" event.
     */
    public function forceDeleted(Residence $residence): void
    {
        ResidenceStatsView::where('residence_id', $residence->id)->delete();
        DB::table('unit_user_stats_view')->where('residence_id', $residence->id)->delete();
    }
}
