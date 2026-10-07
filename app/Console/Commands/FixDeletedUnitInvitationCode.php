<?php

namespace App\Console\Commands;

use App\Models\Unit;
use Illuminate\Console\Command;

class FixDeletedUnitInvitationCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-deleted-unit-invitation-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invitation codes for deleted units by appending _deleted_YYYYMMDD_HHIISS format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix deleted unit invitation codes...');

        // Get soft deleted units that don't already have the deleted format
        $deletedUnits = Unit::onlyTrashed()
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Owner code is not null and doesn't have deleted format
                    $q->whereNotNull('invitation_code_owner')
                        ->where('invitation_code_owner', 'NOT LIKE', '%_deleted_%');
                })->orWhere(function ($q) {
                    // Tenant code is not null and doesn't have deleted format
                    $q->whereNotNull('invitation_code_tenant')
                        ->where('invitation_code_tenant', 'NOT LIKE', '%_deleted_%');
                });
            })
            ->get();

        if ($deletedUnits->isEmpty()) {
            $this->info('No deleted units found that need invitation code updates.');

            return 0;
        }

        $this->info("Found {$deletedUnits->count()} deleted units that need invitation code updates.");

        $updatedCount = 0;
        $progressBar = $this->output->createProgressBar($deletedUnits->count());
        $progressBar->start();

        foreach ($deletedUnits as $record) {
            // Prepare the update data
            $updateData = [];

            // Use the actual deletion date for the timestamp
            $deletedTimestamp = $record->deleted_at->format('Ymd_His');

            // Only update owner code if it exists
            if ($record->invitation_code_owner) {
                $updateData['invitation_code_owner'] = $record->invitation_code_owner.'_deleted_'.$deletedTimestamp;
            }

            // Only update tenant code if it exists
            if ($record->invitation_code_tenant) {
                $updateData['invitation_code_tenant'] = $record->invitation_code_tenant.'_deleted_'.$deletedTimestamp;
            }

            if (! empty($updateData)) {
                $record->update($updateData);
                $updatedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info('Process completed!');
        $this->info("Units updated: {$updatedCount}");

        return 0;
    }
}
