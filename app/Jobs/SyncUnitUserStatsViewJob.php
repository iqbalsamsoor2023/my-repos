<?php

namespace App\Jobs;

use App\Services\UnitUserStatsViewSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUnitUserStatsViewJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public int $uniqueFor = 30;

    public function __construct(public int $residenceId)
    {
        $this->onQueue('StatsSync');
    }

    public function uniqueId(): string
    {
        return (string) $this->residenceId;
    }

    public function handle(): void
    {
        UnitUserStatsViewSyncService::syncOne($this->residenceId);
    }
}
