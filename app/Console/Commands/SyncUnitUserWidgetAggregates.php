<?php

namespace App\Console\Commands;

use App\Services\UnitUserStatsViewSyncService;
use Illuminate\Console\Command;

class SyncUnitUserWidgetAggregates extends Command
{
    protected $signature = 'unit-user:sync-widget-aggregates';

    protected $description = 'Refresh cached widget aggregates for the resident dashboard';

    public function handle(): int
    {
        $this->info('Syncing resident widget aggregates...');

        $startTime = microtime(true);
        UnitUserStatsViewSyncService::syncWidgetAggregates();
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info("Resident widget aggregates synced in {$elapsed}s");

        return Command::SUCCESS;
    }
}
