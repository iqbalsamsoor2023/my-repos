<?php

namespace App\Console\Commands\DataUpdate;

use App\Models\Residence;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSgocUserCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-sgoc-user-company';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update company_id of the SC account following PM account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting company_id sync...');

            $residences = Residence::with('residenceGuardUser')->get();
            $bar = $this->output->createProgressBar($residences->count());

            $updatedCount = 0;
            $mismatchCount = 0;

            $bar->start();

            foreach ($residences as $residence) {
                $bar->advance();

                $residenceGuardUser = $residence->residenceGuardUser;

                // Skip if no SGOC user or no company_id set on residence
                if (! $residenceGuardUser || ! $residence->sgoc_company_id) {
                    continue;
                }

                // Check mismatch
                if ($residenceGuardUser->company_id !== $residence->sgoc_company_id) {
                    $mismatchCount++;

                    // Perform update
                    $residenceGuardUser->update([
                        'company_id' => $residence->sgoc_company_id,
                    ]);

                    $updatedCount++;

                    // Optional: log detail
                    $this->line("Updated: Residence ID {$residence->id} | SGOC User ID {$residenceGuardUser->id}");
                }
            }

            $bar->finish();
            $this->newLine(2);

            $this->info('Sync complete.');
            $this->info("Total mismatched: {$mismatchCount}");
            $this->info("Total users updated: {$updatedCount}");

            return Command::SUCCESS;
        } catch (Exception $ex) {
            DB::rollBack();
            $this->error("An error occurred: {$ex->getMessage()}");

            return Command::FAILURE;
        }
    }
}
