<?php

namespace App\Console\Commands\Generator;

use App\Helpers\HomeIdGenerator;
use App\Models\Residence;
use App\Models\Unit;
use Illuminate\Console\Command;

class GenerateHomeId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:homeid {residenceId : The ID of the Residence}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a HomeId based on a ResidenceId';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $residenceId = $this->argument('residenceId');

        if (empty($residenceId)) {
            $this->error('The Residence ID cannot be empty.');

            return 1;
        }

        $residence = Residence::find($residenceId);

        if (! $residence) {
            $this->error("The Residence with ID {$residenceId} does not exist.");

            return 1;
        }

        $units = Unit::where('residence_id', $residenceId)->get();

        $progressBar = $this->output->createProgressBar(count($units));

        foreach ($units as $unit) {
            $homeId = $this->generateHomeId($unit);

            if (! $homeId) {
                $this->error("Failed to generate HomeId for Unit ID {$unit->id}.");

                continue;
            }

            $unit->home_id = $homeId;
            $unit->save();

            $this->info("Generated HomeId '{$homeId}' for Unit ID {$unit->id}'.");

            $progressBar->advance();
        }

        $progressBar->finish();

        return 0;
    }

    /**
     * Generate a HomeId for a given unit.
     *
     * @param  Unit  $unit  The unit for which to generate the HomeId.
     * @return string|null The generated HomeId, or null if the generation failed.
     */
    private function generateHomeId(Unit $unit): ?string
    {
        return HomeIdGenerator::generate($unit);
    }
}
