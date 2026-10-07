<?php

namespace App\Console\Commands\OneTime;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCreatedAtForVisitorLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitorlogs:update-created-at {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update created_at for visitor logs to match arrival_time for specific date and time range';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inputDate = $this->argument('date');

        $date = Carbon::createFromFormat('Y/m/d', $inputDate);

        $startTime = $date->copy()->setTime(15, 0, 0); // 15:00:00
        $endTime = $date->copy()->setTime(16, 0, 0); // 16:00:00

        // Query to get the affected rows count
        $totalRecords = DB::table('visitor_logs')
            ->whereBetween('arrival_time', [$startTime, $endTime])
            ->whereDate('arrival_time', '=', $date->toDateString())
            ->count();

        if ($totalRecords === 0) {
            $this->info("No records found for the specified time range on {$date->toDateString()}.");

            return;
        }

        // Create a progress bar
        $this->output->progressStart($totalRecords);

        // Process each record individually to show progress
        DB::table('visitor_logs')
            ->whereBetween('arrival_time', [$startTime, $endTime])
            ->whereDate('arrival_time', '=', $date->toDateString())
            ->orderBy('id') // Ensure consistent order
            ->chunkById(100, function ($records) use (&$progressBar) {
                foreach ($records as $record) {
                    // Update each record
                    DB::table('visitor_logs')
                        ->where('id', $record->id)
                        ->update(['created_at' => $record->arrival_time]);

                    // Advance the progress bar
                    $this->output->progressAdvance();
                }
            });

        // Finish the progress bar
        $this->output->progressFinish();

        // Output a success message
        $this->info("Successfully updated {$totalRecords} records from {$startTime} to {$endTime} on {$date->toDateString()}.");
    }
}
