<?php

namespace App\Console\Commands;

use App\Services\ParcelStatsViewSyncService;
use Illuminate\Console\Command;

class SyncParcelWidgetAggregates extends Command
{
    protected $signature = 'parcel:sync-widget-aggregates';

    protected $description = 'Refresh pre-computed parcel widget aggregates (runs every 5 minutes via scheduler)';

    public function handle(): int
    {
        $start = microtime(true);
        ParcelStatsViewSyncService::syncWidgetAggregates();
        $elapsed = round(microtime(true) - $start, 2);

        $this->info("Parcel widget aggregates refreshed in {$elapsed}s");

        return Command::SUCCESS;
    }
}
