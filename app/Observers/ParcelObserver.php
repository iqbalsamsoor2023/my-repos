<?php

namespace App\Observers;

use App\Jobs\SyncParcelStatsViewJob;
use App\Models\Parcel;
use Illuminate\Support\Str;

class ParcelObserver
{
    public function creating(Parcel $parcel): void
    {
        $parcel->qr_code = Str::random(50);
    }

    public function saved(Parcel $parcel): void
    {
        SyncParcelStatsViewJob::dispatch($parcel->id)->afterCommit();
    }

    public function deleted(Parcel $parcel): void
    {
        SyncParcelStatsViewJob::dispatch($parcel->id)->afterCommit();
    }

    public function restored(Parcel $parcel): void
    {
        SyncParcelStatsViewJob::dispatch($parcel->id)->afterCommit();
    }

    public function forceDeleted(Parcel $parcel): void
    {
        SyncParcelStatsViewJob::dispatch($parcel->id)->afterCommit();
    }
}
