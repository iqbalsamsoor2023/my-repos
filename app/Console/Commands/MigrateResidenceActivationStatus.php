<?php

namespace App\Console\Commands;

use App\Models\Residence;
use Illuminate\Console\Command;

class MigrateResidenceActivationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residence:activation-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combine is_active and is_demo data into one status column.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $residences = Residence::withTrashed()->get();

        $bar = $this->output->createProgressBar(count($residences));
        $bar->start();

        Residence::withTrashed()->limit(400)->chunk(100, function ($residences) use ($bar) {
            foreach ($residences as $residence) {
                $isActive = $residence->is_active;
                $isDemo = $residence->is_demo;
                if (! empty($isActive) && empty($isDemo)) {
                    $residenceStatus = $isActive;
                } elseif (empty($isActive) && ! empty($isDemo)) {
                    $residenceStatus = $isDemo;
                } else {
                    $residenceStatus = 0;
                }
                switch ($residenceStatus) {
                    case 1:
                        $residenceActivationStatusId = 5;
                        break;
                    case 0:
                        $residenceActivationStatusId = 2;
                        break;
                    default:
                        $residenceActivationStatusId = 5;
                        break;
                }

                $residence->update([
                    'residence_activation_status_id' => $residenceActivationStatusId,
                ]);

                $bar->advance();
            }
        });

        $bar->finish();

        return Command::SUCCESS;
    }
}
