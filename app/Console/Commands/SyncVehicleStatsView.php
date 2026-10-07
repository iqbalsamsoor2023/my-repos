<?php

namespace App\Console\Commands;

use App\Services\VehicleStatsViewSyncService;
use Illuminate\Console\Command;

class SyncVehicleStatsView extends Command
{
    protected $signature = 'vehicle:sync-stats-view {--chunk=1000 : Chunk size for batch processing}';

    protected $description = 'Sync the vehicle_stats_view read model';

    public function handle(): int
    {
        $this->info('Syncing vehicle stats view...');

        $startTime = microtime(true);
        $chunkSize = max(1, (int) $this->option('chunk'));
        $total = VehicleStatsViewSyncService::syncAll($chunkSize, function (int $processed) {
            $this->output->write('.');
        });
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("Synced {$total} vehicles in {$elapsed}s");

        $this->info('Syncing vehicle widget aggregates...');
        $aggStart = microtime(true);
        VehicleStatsViewSyncService::syncWidgetAggregates();
        $aggElapsed = round(microtime(true) - $aggStart, 2);
        $this->info("Vehicle widget aggregates synced in {$aggElapsed}s");

        return Command::SUCCESS;
    }
}
