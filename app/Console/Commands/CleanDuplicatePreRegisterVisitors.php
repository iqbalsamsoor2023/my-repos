<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicatePreRegisterVisitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:preregister-visitors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete duplicate preregister visitor records and keep only the latest one for each group.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for duplicate preregister_visitors...');

        // Step 1: Get IDs to keep (latest per group)
        $keepIds = DB::table('preregister_visitors as pv')
            ->join('units as u', 'pv.unit_id', '=', 'u.id')
            ->join('visitors as v', 'pv.visitor_id', '=', 'v.id')
            ->where('u.residence_id', 3724)
            ->selectRaw('MAX(pv.id) as id')
            ->groupBy(
                'u.unit_number',
                'v.name',
                'pv.arrival_type',
                'pv.vehicle_type',
                'pv.visitor_purpose',
                'pv.vehicle_plate_no',
                'pv.validity_start_date',
                'pv.validity_end_date',
                'pv.unit_id',
                'pv.user_id',
                'pv.is_multiple_entry',
                'pv.is_qr_code_expired'
            )
            ->pluck('id')
            ->toArray();

        // Step 2: Find duplicates to delete
        $duplicateIds = DB::table('preregister_visitors as pv')
            ->join('units as u', 'pv.unit_id', '=', 'u.id')
            ->where('u.residence_id', 3724)
            ->whereNotIn('pv.id', $keepIds)
            ->pluck('pv.id')
            ->toArray();

        $total = count($duplicateIds);

        if ($total === 0) {
            $this->info('No duplicates found. Nothing to delete.');

            return 0;
        }

        $this->warn("$total duplicate records will be deleted.");
        if (! $this->confirm('Do you wish to continue?', false)) {
            $this->info('Operation cancelled by user.');

            return 0;
        }

        $this->info("Deleting $total duplicate records...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($duplicateIds, 500) as $chunk) {
            DB::table('preregister_visitors')
                ->whereIn('id', $chunk)
                ->delete();

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Cleanup completed. $total records deleted.");

        return 0;
    }
}
