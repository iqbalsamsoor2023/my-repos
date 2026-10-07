<?php

namespace App\Console\Commands;

use App\Models\ActivationModule;
use App\Models\ResidenceFeature;
use Illuminate\Console\Command;

class ActivationModulesDataMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activationModules:data-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move activation modules of Residence module to residence feature table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bar = $this->output->createProgressBar(ActivationModule::where('module', 'residence')->withTrashed()->count());
        $bar->start();

        $activationModules = ActivationModule::where('module', 'residence')->withTrashed()->get();

        $bar = $this->output->createProgressBar(count($activationModules));
        $bar->start();

        ActivationModule::where('module', 'residence')->withTrashed()->limit(400)->chunk(100, function ($activationModules) use ($bar) {
            foreach ($activationModules as $activationModule) {
                $residenceFeature = ResidenceFeature::where('residence_id', $activationModule->residence_id)
                    ->where('feature_id', $activationModule->module_type)
                    ->first();

                if (! $residenceFeature) {
                    ResidenceFeature::create([
                        'residence_id' => $activationModule->residence_id,
                        'feature_id' => $activationModule->module_type,
                        'is_active' => $activationModule->is_active,
                    ]);
                }

                $activationModule->delete();

                $bar->advance();
            }
        });

        $bar->finish();

        return Command::SUCCESS;
    }
}
