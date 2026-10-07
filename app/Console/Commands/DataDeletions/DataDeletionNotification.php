<?php

namespace App\Console\Commands\DataDeletions;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DataDeletionNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-deletion:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SOP delete Notifications older than 2 weeks.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $expiryDate = Carbon::now()->subWeeks(2)->toDateString();

        $query = Notification::whereDate('created_at', '<', $expiryDate);
        $totalData = $query->count();

        if ($totalData === 0) {
            $this->info("No notifications to delete before $expiryDate.");
            return Command::SUCCESS;
        }

        $this->info("Deleting $totalData notifications created before $expiryDate...");

        $bar = $this->output->createProgressBar($totalData);

        $bar->start();

        // Efficiently process in chunks to avoid memory issues
        $query->chunkById(200, function ($notifications) use ($bar) {
            foreach ($notifications as $notification) {
                $notification->delete();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Deleted $totalData notifications.");
        Log::info("DataDeletionNotification: Deleted $totalData notifications before $expiryDate.");

        return Command::SUCCESS;
    }
}
