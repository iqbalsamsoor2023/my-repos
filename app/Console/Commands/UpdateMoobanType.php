<?php

namespace App\Console\Commands;

use App\Enums\Residence\MoobanType;
use App\Models\Residence;
use Illuminate\Console\Command;

class UpdateMoobanType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:residence-mooban-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refactor mooban type to use integer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalResidences = Residence::count();

        if ($totalResidences == 0) {
            $this->info('No Residence found!');

            return;
        }

        $this->info("Processing {$totalResidences} residences...");

        $progressBar = $this->output->createProgressBar($totalResidences);
        $progressBar->start();

        Residence::withTrashed()->chunkById(500, function ($residences) use ($progressBar) {
            foreach ($residences as $residence) {
                $moobanTypeMap = [
                    'Public' => MoobanType::PUBLIC,
                    'Residence' => MoobanType::RESIDENCE,
                    'Factory' => MoobanType::FACTORY,
                    'PMOC' => MoobanType::PMOC,
                    'Demo_Sg' => MoobanType::DEMO_FOR_SG,
                ];

                if ($residence->sub_type == 0) {
                    $residence->sub_type = null;
                }

                $residence->mooban_type = $moobanTypeMap[$residence->mooban_type] ?? 0;
                $residence->save();

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->info('Mooban Type IDs updated in residences successfully.');
    }
}
