<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Residence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePersonInChargeToJuristicDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residence:migrate-juristic-details
                            {--dry-run : Preview changes without applying them}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing person_in_charges data to juristic_details (one-time operation)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('🏢 Residence Data Migration: Person in Charge → Juristic Details');
        $this->newLine();

        // Check if there are residences to migrate
        $residencesCount = Residence::whereNotNull('person_in_charges')
            ->where('person_in_charges', '!=', '[]')
            ->where('person_in_charges', '!=', '{}')
            ->where(function ($query) {
                $query->whereNull('juristic_details')
                    ->orWhere('juristic_details', '[]')
                    ->orWhere('juristic_details', '{}');
            })
            ->count();

        if ($residencesCount === 0) {
            $this->warn('⚠️  No residences found that need migration.');
            $this->info('All residences either have no person_in_charges or already have juristic_details.');

            return Command::SUCCESS;
        }

        $this->info("📊 Found {$residencesCount} residence(s) that need migration.");
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Show preview of what will happen
        $this->showPreview();

        // Confirm action unless forced
        if (! $isDryRun && ! $isForced) {
            if (! $this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');

                return Command::SUCCESS;
            }
        }

        // Perform the migration
        return $this->performMigration($isDryRun);
    }

    /**
     * Show a preview of the migration
     */
    private function showPreview(): void
    {
        $this->info('📋 Migration Preview:');

        $sampleResidence = Residence::whereNotNull('person_in_charges')
            ->where('person_in_charges', '!=', '[]')
            ->where('person_in_charges', '!=', '{}')
            ->where(function ($query) {
                $query->whereNull('juristic_details')
                    ->orWhere('juristic_details', '[]')
                    ->orWhere('juristic_details', '{}');
            })
            ->first();

        if ($sampleResidence && is_array($sampleResidence->person_in_charges) && ! empty($sampleResidence->person_in_charges)) {
            $firstPerson = $sampleResidence->person_in_charges[0];
            $remainingCount = count($sampleResidence->person_in_charges) - 1;

            $this->table(
                ['Field', 'Current (person_in_charges)', 'New (juristic_details)'],
                [
                    ['Name', $firstPerson['name'] ?? 'N/A', $firstPerson['name'] ?? 'N/A'],
                    ['Email', $firstPerson['email'] ?? 'N/A', $firstPerson['email'] ?? 'N/A'],
                    ['Phone', $firstPerson['contact'] ?? 'N/A', $firstPerson['contact'] ?? 'N/A'],
                    ['Line ID', $firstPerson['line_id'] ?? 'N/A', $firstPerson['line_id'] ?? 'N/A'],
                ]
            );

            $this->info('📝 After migration:');
            $this->info('   • Juristic Details: 1 person (the first from person_in_charges)');
            $this->info("   • Person in Charges: {$remainingCount} remaining person(s)");
        }

        $this->newLine();
    }

    /**
     * Perform the actual migration
     */
    private function performMigration(bool $isDryRun): int
    {
        $totalProcessed = 0;
        $totalMigrated = 0;
        $totalErrors = 0;

        try {
            DB::beginTransaction();

            $this->info($isDryRun ? '🔍 Simulating migration...' : '🚀 Starting migration...');
            $progressBar = $this->output->createProgressBar();

            Residence::whereNotNull('person_in_charges')
                ->where('person_in_charges', '!=', '[]')
                ->where('person_in_charges', '!=', '{}')
                ->where(function ($query) {
                    $query->whereNull('juristic_details')
                        ->orWhere('juristic_details', '[]')
                        ->orWhere('juristic_details', '{}');
                })
                ->chunk(50, function ($residences) use (&$totalProcessed, &$totalMigrated, &$totalErrors, $isDryRun, $progressBar) {
                    foreach ($residences as $residence) {
                        $totalProcessed++;
                        $progressBar->advance();

                        try {
                            $personInCharges = $residence->person_in_charges;

                            if (is_array($personInCharges) && ! empty($personInCharges)) {
                                // Take the first person as juristic details
                                $firstPerson = $personInCharges[0];

                                // Map the data structure
                                $juristicDetails = [
                                    'name' => $firstPerson['name'] ?? '',
                                    'email' => $firstPerson['email'] ?? '',
                                    'phone_no' => $firstPerson['contact'] ?? '',
                                    'line_id' => $firstPerson['line_id'] ?? '',
                                ];

                                // Remove the first person and keep the rest
                                $remainingPersons = array_slice($personInCharges, 1);

                                if (! $isDryRun) {
                                    // Update the residence
                                    $residence->update([
                                        'juristic_details' => $juristicDetails,
                                        'person_in_charges' => $remainingPersons,
                                    ]);
                                }

                                $totalMigrated++;
                            }
                        } catch (Exception $e) {
                            $totalErrors++;
                            $this->error("Error processing residence ID {$residence->id}: ".$e->getMessage());
                        }
                    }
                });

            $progressBar->finish();
            $this->newLine(2);

            if (! $isDryRun) {
                DB::commit();
                $this->info('✅ Migration completed successfully!');
            } else {
                DB::rollBack();
                $this->info('✅ Dry run completed successfully!');
            }

            // Show results
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Processed', $totalProcessed],
                    ['Successfully Migrated', $totalMigrated],
                    ['Errors', $totalErrors],
                ]
            );

            if (! $isDryRun && $totalMigrated > 0) {
                $this->newLine();
                $this->info('🎉 Data migration completed!');
                $this->info('   • Juristic details have been populated from the first person in charge');
                $this->info('   • Remaining person in charges are now available as a repeater');
                $this->info('   • You can now use the updated form structure');
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('❌ Migration failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
