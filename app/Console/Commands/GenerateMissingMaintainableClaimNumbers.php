<?php

namespace App\Console\Commands;

use Exception;
use App\Helpers\MaintenanceClaimNoGenerator;
use App\Models\Maintenance;
use App\Models\Unit;
use Illuminate\Console\Command;

class GenerateMissingMaintainableClaimNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-missing-maintainable-claim-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate unique maintainable claim numbers for maintenance records missing them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maintenances = Maintenance::whereNull('maintainable_claim_number')->get();

        if ($maintenances->isEmpty()) {
            $this->info('No maintenance records are missing maintainable claim number.');

            return Command::SUCCESS;
        }

        $this->info("Generating maintainable claim numbers for {$maintenances->count()} records...");

        $this->output->progressStart($maintenances->count());

        foreach ($maintenances as $maintenance) {
            $maintenance->load('maintainable');

            if (! $maintenance->maintainable) {
                $this->warn("Maintenance ID {$maintenance->id} has no maintainable entity. Skipping.");
                $this->output->progressAdvance();

                continue;
            }

            try {
                $claimNumber = $maintenance->maintainable_type === Unit::class
                    ? MaintenanceClaimNoGenerator::generate($maintenance)
                    : MaintenanceClaimNoGenerator::generatePublicClaimNo($maintenance);

                $maintenance->maintainable_claim_number = $claimNumber;
                $maintenance->save();
            } catch (Exception $e) {
                $this->error("Failed to generate claim number for Maintenance ID {$maintenance->id}: ".$e->getMessage());
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info('All missing maintainable claim numbers have been generated.');

        return Command::SUCCESS;
    }
}
