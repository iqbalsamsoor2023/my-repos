<?php

namespace App\Console\Commands;

use App\Services\UnitStatsViewSyncService;
use Illuminate\Console\Command;

class SyncUnitStatsView extends Command
{
    protected $signature = 'unit:sync-stats-view {--chunk=1 : Chunk size for batch processing}';

    protected $description = 'Sync the units_stats_view read model (CQRS-lite)';

    public function handle(): int
    {
        $this->info('Syncing unit stats view...');

        $startTime = microtime(true);
        $chunkSize = max(1, (int) $this->option('chunk'));
        $total = UnitStatsViewSyncService::syncAll($chunkSize);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("Synced {$total} residences in {$elapsed}s");

        $this->info('Syncing widget aggregates...');
        $aggStart = microtime(true);
        UnitStatsViewSyncService::syncWidgetAggregates();
        $aggElapsed = round(microtime(true) - $aggStart, 2);
        $this->info("Widget aggregates synced in {$aggElapsed}s");

        return Command::SUCCESS;
    }
}
