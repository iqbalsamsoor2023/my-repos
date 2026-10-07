<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixVisitingArrangementsCreatedAt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:visiting-arrangements-created-at';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update visiting_arrangements.created_at with visitor_logs.created_at where null';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Use SYSTEM timezone to preserve exact datetime values without conversion
        try { DB::statement("SET SESSION time_zone = 'SYSTEM'"); } catch (\Throwable $e) {}

        $this->info('Starting update of visiting_arrangements.created_at ...');

        // Count total rows that need fixing
        $total = DB::table('visiting_arrangements')
            ->whereNull('created_at')
            ->count();

        if ($total === 0) {
            $this->info('No rows found with NULL created_at.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} rows to update.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Use direct SQL UPDATE with JOIN to preserve exact datetime values without PHP conversion
        // This avoids timezone interpretation that happens when reading through PDO
        $updated = DB::statement('
            UPDATE visiting_arrangements va
            INNER JOIN visitor_logs vl ON va.visitor_log_id = vl.id
            SET va.created_at = vl.created_at
            WHERE va.created_at IS NULL
        ');

        $bar->advance($total);
        $bar->finish();

        $this->newLine();
        $this->info('All missing created_at values have been updated.');

        return Command::SUCCESS;
    }
}
