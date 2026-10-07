<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DeleteOrphanVisitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $visitorIds;
    public bool $dryRun;

    /**
     * Create a new job instance.
     */
    public function __construct(array $visitorIds, bool $dryRun = false)
    {
        $this->visitorIds = $visitorIds;
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logFile = storage_path('logs/visitor-id-deleted.log');
        $dir = dirname($logFile);

        // ensure folder exists
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $visitors = DB::table('visitors')
            ->whereIn('id', $this->visitorIds)
            ->get();

        foreach ($visitors as $visitor) {

            // =========================
            // AUDIT LOG (JSONL)
            // =========================
            file_put_contents(
                $logFile,
                json_encode([
                    'visitor_id' => $visitor->id,
                    'name' => $visitor->name,
                    'contact_no' => $visitor->contact_no,
                    'id_number' => $visitor->id_number,
                    'dry_run' => $this->dryRun,
                    'deleted_at' => now()->toDateTimeString(),
                ]) . PHP_EOL,
                FILE_APPEND
            );

            // =========================
            // DELETE ONLY IF NOT DRY RUN
            // =========================
            if (! $this->dryRun) {
                DB::table('visitors')
                    ->where('id', $visitor->id)
                    ->delete();
            }
        }
    }
}
