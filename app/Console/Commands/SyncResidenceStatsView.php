<?php

namespace App\Console\Commands;

use App\Services\ResidenceStatsViewSyncService;
use Illuminate\Console\Command;

class SyncResidenceStatsView extends Command
{
    protected $signature = 'residence:sync-stats-view {--chunk=500 : Chunk size for batch processing}';

    protected $description = 'Sync the residence_stats_view read model (CQRS-lite)';

    public function handle(): int
    {
        $this->info('Syncing residence stats view...');

        $startTime = microtime(true);
        $chunkSize = (int) $this->option('chunk');

        $total = ResidenceStatsViewSyncService::syncAll($chunkSize);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("Synced {$total} residences in {$elapsed}s");

        // Also sync widget aggregates
        $this->info('Syncing widget aggregates...');
        $aggStart = microtime(true);
        ResidenceStatsViewSyncService::syncWidgetAggregates();
        $aggElapsed = round(microtime(true) - $aggStart, 2);
        $this->info("Widget aggregates synced in {$aggElapsed}s");

        return Command::SUCCESS;
    }
}
