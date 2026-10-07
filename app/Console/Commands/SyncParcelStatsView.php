<?php

namespace App\Console\Commands;

use App\Services\ParcelStatsViewSyncService;
use Illuminate\Console\Command;

class SyncParcelStatsView extends Command
{
    protected $signature = 'parcel:sync-stats-view {--chunk=5000 : ID range size per SQL batch}';

    protected $description = 'Sync the parcel_stats_view read model';

    public function handle(): int
    {
        $this->info('Syncing parcel stats view...');

        $startTime = microtime(true);
        $chunkSize = max(1, (int) $this->option('chunk'));
        $total = ParcelStatsViewSyncService::syncAll($chunkSize, function (int $processed) {
            $this->output->write('.');
        });
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("Synced {$total} parcels in {$elapsed}s");

        $this->info('Syncing parcel widget aggregates...');
        $aggStart = microtime(true);
        ParcelStatsViewSyncService::syncWidgetAggregates();
        $aggElapsed = round(microtime(true) - $aggStart, 2);
        $this->info("Parcel widget aggregates synced in {$aggElapsed}s");

        return Command::SUCCESS;
    }
}
