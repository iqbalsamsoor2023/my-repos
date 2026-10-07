<?php

namespace App\Console\Commands;

use App\Helpers\HomeIdGenerator;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateHomeIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'units:fix-duplicate-home-ids
                            {--chunk=100 : Number of records to process per chunk}
                            {--dry-run : Run without making changes to see what would be updated}
                            {--skip-errors : Skip units with missing data instead of treating as errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate home_id values in units table, including soft-deleted records';

    private int $processedCount = 0;

    private int $updatedCount = 0;

    private int $skippedCount = 0;

    private int $errorCount = 0;

    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->executeCommand();
    }

    /**
     * Main command execution logic
     */
    private function executeCommand(): int
    {
        $isDryRun = $this->option('dry-run');
        $skipErrors = $this->option('skip-errors');
        $chunkSize = (int) $this->option('chunk');

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made');
        }

        if ($skipErrors) {
            $this->info('Skip errors mode - units with missing data will be skipped');
        }

        $this->info('Starting to fix duplicate home IDs...');
        $this->newLine();

        // Count total duplicates for progress tracking
        $totalDuplicates = $this->countDuplicateHomeIds();

        if ($totalDuplicates === 0) {
            $this->info('No duplicate home IDs found!');

            return Command::SUCCESS;
        }

        $this->info("Found {$totalDuplicates} home_ids with duplicates");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalDuplicates);
        $progressBar->start();

        // Process duplicate home_ids in chunks
        $this->processDuplicateHomeIdsInChunks($chunkSize, $isDryRun, $skipErrors, $progressBar);

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($isDryRun);

        if ($this->errorCount > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Count duplicate home_ids without loading them into memory
     */
    private function countDuplicateHomeIds(): int
    {
        return DB::table('units')
            ->whereNotNull('home_id')
            ->whereNull('deleted_at')
            ->select('home_id')
            ->groupBy('home_id')
            ->havingRaw('count(*) > 1')
            ->count()
            + DB::table('units')
                ->whereNotNull('home_id')
                ->whereNotNull('deleted_at')
                ->select('home_id')
                ->groupBy('home_id')
                ->havingRaw('count(*) > 1')
                ->count();
    }

    /**
     * Process duplicate home_ids in chunks to avoid memory issues
     */
    private function processDuplicateHomeIdsInChunks(int $chunkSize, bool $isDryRun, bool $skipErrors, $progressBar): void
    {
        // Process both active and soft-deleted records
        DB::table('units')
            ->whereNotNull('home_id')
            ->select('home_id', DB::raw('count(*) as total'))
            ->groupBy('home_id')
            ->having('total', '>', 1)
            ->orderBy('home_id')
            ->chunk($chunkSize, function ($duplicateHomeIds) use ($isDryRun, $skipErrors, $progressBar, $chunkSize) {
                foreach ($duplicateHomeIds as $duplicateHomeId) {
                    $this->processDuplicateHomeId($duplicateHomeId->home_id, $chunkSize, $isDryRun, $skipErrors);
                    $progressBar->advance();
                }
            });
    }

    /**
     * Process all units with a specific duplicate home_id in chunks
     */
    private function processDuplicateHomeId(string $homeId, int $chunkSize, bool $isDryRun, bool $skipErrors): void
    {
        $isFirstUnit = true;

        // Process units in chunks with minimal relationship loading
        Unit::withTrashed()
            ->where('home_id', $homeId)
            ->orderBy('id')
            ->with([
                'residence' => function ($query) {
                    $query->withTrashed()->select('id', 'subdistrict_id');
                },
                'residence.subdistrict:id,code',
            ])
            ->chunk($chunkSize, function ($units) use (&$isFirstUnit, $isDryRun, $skipErrors) {
                foreach ($units as $unit) {
                    $this->processedCount++;

                    // Keep the first unit's home_id unchanged
                    if ($isFirstUnit) {
                        $isFirstUnit = false;

                        continue;
                    }

                    try {
                        // Validate required relationships
                        if (! $unit->residence) {
                            if ($skipErrors) {
                                $this->skippedCount++;

                                continue;
                            }
                            $this->errors[] = "Unit ID {$unit->id}: Missing residence relationship";
                            $this->errorCount++;

                            continue;
                        }

                        if (! $unit->residence->subdistrict) {
                            if ($skipErrors) {
                                $this->skippedCount++;

                                continue;
                            }
                            $this->errors[] = "Unit ID {$unit->id}: Missing subdistrict relationship";
                            $this->errorCount++;

                            continue;
                        }

                        // Generate new home_id
                        $newHomeId = HomeIdGenerator::generate($unit);

                        if (! $isDryRun) {
                            // Update directly without triggering model events
                            DB::table('units')
                                ->where('id', $unit->id)
                                ->update([
                                    'home_id' => $newHomeId,
                                    'updated_at' => now(),
                                ]);
                        }

                        $this->updatedCount++;
                    } catch (\Exception $e) {
                        if ($skipErrors) {
                            $this->skippedCount++;

                            continue;
                        }
                        $this->errors[] = "Unit ID {$unit->id}: {$e->getMessage()}";
                        $this->errorCount++;
                    }
                }
            });
    }

    /**
     * Display the results of the operation
     */
    private function displayResults(bool $isDryRun): void
    {
        $this->info('Results:');
        $this->info("   Processed: {$this->processedCount} units");
        $this->info('   '.($isDryRun ? 'Would update' : 'Updated').": {$this->updatedCount} units");

        if ($this->skippedCount > 0) {
            $this->info("   Skipped: {$this->skippedCount} units (missing required data)");
        }

        $this->info("   Errors: {$this->errorCount}");

        if ($this->errorCount > 0) {
            $this->newLine();
            $this->error('Errors encountered:');
            $maxErrors = min(50, count($this->errors));
            for ($i = 0; $i < $maxErrors; $i++) {
                $this->error("   - {$this->errors[$i]}");
            }
            if (count($this->errors) > 50) {
                $remaining = count($this->errors) - 50;
                $this->error("   ... and {$remaining} more errors (showing first 50)");
            }
        } else {
            $this->newLine();
            if ($isDryRun) {
                $this->info('Dry run completed successfully! Run without --dry-run to apply changes.');
            } else {
                $this->info('All duplicate home IDs fixed successfully!');
            }
        }

        if ($this->skippedCount > 0) {
            $this->newLine();
            $this->warn('Note: Some units were skipped due to missing residence or subdistrict data.');
            $this->warn('These units need their residence relationship to be fixed before regenerating home_id.');
        }
    }
}
