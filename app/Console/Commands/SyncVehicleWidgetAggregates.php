<?php

namespace App\Console\Commands;

use App\Services\VehicleStatsViewSyncService;
use Illuminate\Console\Command;

class SyncVehicleWidgetAggregates extends Command
{
    protected $signature = 'vehicle:sync-widget-aggregates';

    protected $description = 'Refresh pre-computed vehicle widget aggregates (runs every 5 minutes via scheduler)';

    public function handle(): int
    {
        $start = microtime(true);
        VehicleStatsViewSyncService::syncWidgetAggregates();
        $elapsed = round(microtime(true) - $start, 2);

        $this->info("Vehicle widget aggregates refreshed in {$elapsed}s");

        return Command::SUCCESS;
    }
}
