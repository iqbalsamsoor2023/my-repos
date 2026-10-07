<?php

namespace App\Console\Commands;

use App\Models\Residence;
use App\Models\Unit;
use Illuminate\Console\Command;

class UpdateUnitsSubType extends Command
{
    protected $signature = 'units:update-sub-type
                            {--residence= : Update by specific residence_id}
                            {--all : Update all units}';

    protected $description = 'Update sub_type in units table based on residence.sub_type';

    // example:
    // php artisan units:update-sub-type --residence=3019
    // php artisan units:update-sub-type --all

    public function handle(): int
    {
        $residenceId = $this->option('residence');
        $updateAll = $this->option('all');

        if (! $residenceId && ! $updateAll) {
            $this->error('You must provide either --residence=<id> or --all');

            return self::FAILURE;
        }

        if ($residenceId) {
            $residence = Residence::find($residenceId);

            if (! $residence) {
                $this->error("Residence with ID {$residenceId} not found.");

                return self::FAILURE;
            }

            $totalUnits = Unit::where('residence_id', $residence->id)->count();

            $this->info("Updating {$totalUnits} units for residence ID {$residenceId}...");

            $bar = $this->output->createProgressBar($totalUnits);
            $bar->start();

            Unit::where('residence_id', $residence->id)
                ->orderBy('id')
                ->chunkById(500, function ($units) use ($residence, $bar) {
                    foreach ($units as $unit) {
                        $unit->sub_type = $residence->sub_type;
                        $unit->save();
                        $bar->advance();
                    }
                });

            $bar->finish();
            $this->newLine();
            $this->info("Completed updating units for residence ID {$residenceId}.");
        }

        if ($updateAll) {
            $totalUnits = Unit::count();
            $this->info("Updating ALL units ({$totalUnits} total)...");

            $bar = $this->output->createProgressBar($totalUnits);
            $bar->start();

            Unit::orderBy('id')
                ->with('residence')
                ->chunkById(500, function ($units) use ($bar) {
                    foreach ($units as $unit) {
                        if ($unit->residence) {
                            $unit->sub_type = $unit->residence->sub_type;
                            $unit->save();
                        }
                        $bar->advance();
                    }
                });

            $bar->finish();
            $this->newLine();
            $this->info("Completed updating {$totalUnits} units.");
        }

        return self::SUCCESS;
    }
}
