<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteTrackerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-tracker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old tracker data from various tables based on retention policies';

    /**
     * Tracker configurations.
     *
     * Each tracker has:
     * - `table`: Name of the table.
     * - `column`: Column to check against the retention date.
     * - `retention`: Duration to keep data (e.g., 1 month, 6 months).
     *
     * @var array
     */
    protected $trackers = [
        [
            'table' => 'visitor_sequences',
            'column' => 'created_at',
            'retention' => '1 month',
            'date_format' => 'ymd', // Specify the format if needed
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach ($this->trackers as $tracker) {
            $this->deleteOldData($tracker);
        }
    }

    /**
     * Delete old data for a specific tracker.
     */
    private function deleteOldData(array $tracker)
    {
        try {
            // Parse the retention period using Carbon
            $cutoffDate = match ($tracker['retention']) {
                '1 month' => now()->subMonth(),
                '3 months' => now()->subMonths(3),
                '2 weeks' => now()->subWeeks(2),
                '1 week' => now()->subWeek(),
                default => now()->subMonth(), // Default fallback
            };

            // Format the cutoff date according to the tracker configuration
            $formattedCutoffDate = $cutoffDate->format($tracker['date_format']);

            // Delete old data
            $deletedRows = DB::table($tracker['table'])
                ->where($tracker['column'], '<', $formattedCutoffDate)
                ->delete();

            $this->info("Deleted {$deletedRows} records from {$tracker['table']} older than {$tracker['retention']}.");
        } catch (Exception $e) {
            $this->error("Error cleaning tracker data for table {$tracker['table']}: ".$e->getMessage());
        }
    }
}
