<?php

namespace App\Console\Commands;

use App\Jobs\DeleteOrphanVisitorJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanVisitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitors:cleanup-orphans
                            {--batch=10000 : Number of visitor IDs to process per batch}
                            {--limit=200000 : Max visitors to scan}
                            {--dry-run : Only simulate, no delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete visitors not referenced by any visitor tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info("Starting orphan scan...");
        $this->info($dryRun ? "MODE: DRY RUN" : "MODE: DELETE");

        $buffer = [];
        $processed = 0;
        $dispatched = 0;

        DB::table('visitors')
            ->orderBy('id')
            ->chunkById($batchSize, function ($visitors) use (
                &$buffer,
                &$processed,
                &$dispatched,
                $batchSize,
                $limit,
                $dryRun
            ) {

                foreach ($visitors as $visitor) {

                    $isOrphan = !DB::table('visitor_logs')
                        ->where('visitor_id', $visitor->id)->exists()
                        && !DB::table('visitor_logs_archive')
                            ->where('visitor_id', $visitor->id)->exists()
                        && !DB::table('preregister_visitors')
                            ->where('visitor_id', $visitor->id)->exists()
                        && !DB::table('blacklisted_visitors')
                            ->where('visitor_id', $visitor->id)->exists();

                    if ($isOrphan) {
                        $buffer[] = $visitor->id;
                    }

                    $processed++;

                    if (count($buffer) >= $batchSize) {
                        DeleteOrphanVisitorJob::dispatch($buffer, $dryRun);
                        $buffer = [];
                        $dispatched++;
                    }

                    if ($processed >= $limit) {
                        return false;
                    }
                }
            }, 'id');

        if (!empty($buffer)) {
            DeleteOrphanVisitorJob::dispatch($buffer, $dryRun);
            $dispatched++;
        }

        $this->info("Done.");
        $this->info("Processed: {$processed}");
        $this->info("Jobs dispatched: {$dispatched}");

        return self::SUCCESS;
    }
}
