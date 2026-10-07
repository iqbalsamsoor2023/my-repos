<?php

namespace App\Jobs;

use App\Services\ParcelStatsViewSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncParcelStatsViewJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public int $uniqueFor = 30;

    public function __construct(public int $parcelId)
    {
        $this->onQueue('StatsSync');
    }

    public function uniqueId(): string
    {
        return (string) $this->parcelId;
    }

    public function handle(): void
    {
        ParcelStatsViewSyncService::syncOne($this->parcelId);
    }
}
