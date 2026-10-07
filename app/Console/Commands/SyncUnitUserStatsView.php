<?php

namespace App\Console\Commands;

use App\Services\UnitUserStatsViewSyncService;
use Illuminate\Console\Command;

class SyncUnitUserStatsView extends Command
{
    protected $signature = 'unit-user:sync-stats-view {--chunk=5 : Chunk size for batch processing}';

    protected $description = 'Sync the unit_user_stats_view read model';

    public function handle(): int
    {
        $this->info('Syncing unit user stats view...');

        $startTime = microtime(true);
        $chunkSize = max(1, (int) $this->option('chunk'));
        $total = UnitUserStatsViewSyncService::syncAll($chunkSize);
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info("Synced {$total} residences in {$elapsed}s");

        $this->info('Syncing resident widget aggregates...');
        $aggStart = microtime(true);
        UnitUserStatsViewSyncService::syncWidgetAggregates();
        $aggElapsed = round(microtime(true) - $aggStart, 2);
        $this->info("Resident widget aggregates synced in {$aggElapsed}s");

        return Command::SUCCESS;
    }
}
