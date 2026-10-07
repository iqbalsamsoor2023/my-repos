<?php

namespace App\Console\Commands\Generator;

use App\Helpers\MmbIdGenerator;
use App\Models\Residence;
use App\Models\Unit;
use App\Models\UnitUser;
use Illuminate\Console\Command;

class GenerateMmbId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:mmbid {residenceId : The ID of the Residence}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a MmbId based on a ResidenceId';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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
        // Load all units and associated unit users in a single query
        $units = Unit::where('residence_id', $residenceId)->get();

        $progressBar = $this->output->createProgressBar(count($units));

        foreach ($units as $unit) {
            $mmbId = $this->generateMmbId($unit);
            $progressBar->advance();
        }

        $progressBar->finish();

        return 0;
    }

    /**
     * Generate a MmbId for a UnitUser.
     *
     * @param  object  $unitUser
     * @return string|null
     */
    private function generateMmbId($unit)
    {
        return MmbIdGenerator::execute($unit);
    }
}
