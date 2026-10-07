<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteDuplicationSubscriptionExpireRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-duplication-subscription-expire-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all records of the same type, company_id, and residence_id except the latest by created_at';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            // Fetch the latest IDs for each unique combination of type, company_id, residence_id
            $latestIds = DB::table('subscription_expires')
                ->selectRaw('MAX(id) as id')
                ->groupBy('type', 'company_id', 'residence_id')
                ->pluck('id');

            // Delete all rows except the latest ones
            $deletedCount = DB::table('subscription_expires')
                ->whereNotIn('id', $latestIds)
                ->delete();

            DB::commit();

            $this->info("Successfully deleted {$deletedCount} old records.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("Failed to clean up records: {$e->getMessage()}");
        }

        return Command::SUCCESS;
    }
}
