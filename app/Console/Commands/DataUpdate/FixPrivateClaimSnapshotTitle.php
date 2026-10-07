<?php

namespace App\Console\Commands\DataUpdate;

use App\Models\Maintenance;
use App\Models\PrivateClaimItemTitle;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPrivateClaimSnapshotTitle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-private-claim-snapshot-title {--dry-run : Preview changes without writing to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill the title name and name_th inside private_claim_snapshot on the maintenances table from PrivateClaimItemTitle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            DB::beginTransaction();

            $query = Maintenance::whereNotNull('private_claim_snapshot');

            $total = $query->count();
            $bar = $this->output->createProgressBar($total);

            $updated = 0;
            $skipped = 0;

            $query->chunkById(200, function ($maintenances) use ($bar, &$updated, &$skipped, $dryRun) {
                foreach ($maintenances as $maintenance) {
                    $snapshot = $maintenance->private_claim_snapshot;

                    // Nothing to fix if there is no title reference in the snapshot.
                    if (! is_array($snapshot) || empty($snapshot['title']['id'])) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $title = PrivateClaimItemTitle::withTrashed()->find($snapshot['title']['id']);

                    if (! $title) {
                        Log::warning("PrivateClaimItemTitle not found for Maintenance ID {$maintenance->id} (title id {$snapshot['title']['id']})");
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $newName = $title->option_name;
                    $newNameTh = $title->option_name_th;

                    // Skip rows that are already correct.
                    if (($snapshot['title']['name'] ?? null) === $newName
                        && ($snapshot['title']['name_th'] ?? null) === $newNameTh) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $snapshot['title']['name'] = $newName;
                    $snapshot['title']['name_th'] = $newNameTh;

                    if (! $dryRun) {
                        $maintenance->update(['private_claim_snapshot' => $snapshot]);
                    }

                    $updated++;
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();

            if ($dryRun) {
                DB::rollBack();
                $this->info("Dry run complete. Would update: {$updated}, skipped: {$skipped} (total scanned: {$total}). No changes were written.");
            } else {
                DB::commit();
                $this->info("Processing complete! Updated: {$updated}, skipped: {$skipped} (total scanned: {$total}).");
            }

            return Command::SUCCESS;
        } catch (Exception $ex) {
            DB::rollBack();
            $this->error("An error occurred: {$ex->getMessage()}");

            return Command::FAILURE;
        }
    }
}
