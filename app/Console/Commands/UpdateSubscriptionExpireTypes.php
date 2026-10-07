<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateSubscriptionExpireTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:subscription-expire-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update types in subscriptions based on company type';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Processing subscriptions...');

            DB::table('subscription_expires')
                ->where('type', 'Residence')
                ->orderBy('id') // Ensure deterministic processing order
                ->chunk(100, function ($subscriptions) {
                    foreach ($subscriptions as $subscription) {
                        DB::transaction(function () use ($subscription) {
                            $company = DB::table('companies')
                                ->where('id', $subscription->company_id)
                                ->first();

                            // If the company is found and matches certain types
                            if ($company) {
                                if ($company->type === 'Property Management') {
                                    DB::table('subscription_expires')
                                        ->where('id', $subscription->id)
                                        ->update(['type' => 'Property Management']);
                                } elseif ($company->type === 'Fire Insurance') {
                                    DB::table('subscription_expires')
                                        ->where('id', $subscription->id)
                                        ->update(['type' => 'Fire Insurance']);
                                }
                            }

                            // Handle the NULL company_id case
                            if (! $company || ! $subscription->company_id) {
                                DB::table('subscription_expires')
                                    ->where('id', $subscription->id)
                                    ->update(['type' => 'Property Management']);
                            }
                        });
                    }

                    $this->info('Processed a chunk of 100 subscriptions.');
                });

            $this->info('Processing complete!');
        } catch (Exception $e) {
            $this->error("Error occurred: {$e->getMessage()}");
        }

        return Command::SUCCESS;
    }
}
