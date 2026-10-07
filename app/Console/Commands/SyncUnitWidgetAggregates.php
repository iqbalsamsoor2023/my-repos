<?php

namespace App\Console\Commands;

use App\Services\UnitStatsViewSyncService;
use Illuminate\Console\Command;

class SyncUnitWidgetAggregates extends Command
{
    protected $signature = 'unit:sync-widget-aggregates';

    protected $description = 'Refresh cached widget aggregates for the units dashboard';

    public function handle(): int
    {
        $this->info('Syncing unit widget aggregates...');

        $startTime = microtime(true);
        UnitStatsViewSyncService::syncWidgetAggregates();
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info("Unit widget aggregates synced in {$elapsed}s");

        return Command::SUCCESS;
    }
}
