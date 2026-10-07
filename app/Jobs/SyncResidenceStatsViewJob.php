<?php

namespace App\Jobs;

use App\Services\ResidenceStatsViewSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncResidenceStatsViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public int $residenceId
    ) {
        $this->onQueue('StatsSync');
    }

    public function handle(): void
    {
        ResidenceStatsViewSyncService::syncOne($this->residenceId);
    }
}
