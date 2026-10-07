<?php

namespace App\Console\Commands;

use App\Enums\Residence\Features;
use App\Models\Residence;
use App\Models\ResidenceFeature;
use Illuminate\Console\Command;

class StoreExistingResidenceFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residence:feature';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new residence feature data';

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

        Residence::withTrashed()->limit(200)->chunk(100, function ($residences) use ($bar) {
            foreach ($residences as $residence) {
                if (is_null($residence->property_management_user_id) == false) {
                    $residenceFeature = ResidenceFeature::where('residence_id', $residence->id)
                        ->where('feature_id', Features::PROPERTY_MANAGEMENT->value)
                        ->first();

                    if (! $residenceFeature) {
                        ResidenceFeature::create([
                            'residence_id' => $residence->id,
                            'feature_id' => Features::PROPERTY_MANAGEMENT->value,
                            'is_active' => 1,
                        ]);
                    }
                }

                if (is_null($residence->sgoc_residence_guard_user_id) == false) {
                    $residenceFeature = ResidenceFeature::where('residence_id', $residence->id)
                        ->where('feature_id', Features::SECURITY_MANAGEMENT->value)
                        ->first();
                    if (! $residenceFeature) {
                        ResidenceFeature::create([
                            'residence_id' => $residence->id,
                            'feature_id' => Features::SECURITY_MANAGEMENT->value,
                            'is_active' => 1,
                        ]);
                    }
                }

                if (is_null($residence->accountant_user_id) == false) {
                    $residenceFeature = ResidenceFeature::where('residence_id', $residence->id)
                        ->where('feature_id', Features::ACCOUNTING_MANAGEMENT->value)
                        ->first();

                    if (! $residenceFeature) {
                        ResidenceFeature::create([
                            'residence_id' => $residence->id,
                            'feature_id' => Features::ACCOUNTING_MANAGEMENT->value,
                            'is_active' => 1,
                        ]);
                    }
                }

                if (is_null($residence->receptionist_user_id) == false) {
                    $residenceFeature = ResidenceFeature::where('residence_id', $residence->id)
                        ->where('feature_id', Features::RECEPTION_MANAGEMENT->value)
                        ->first();

                    if (! $residenceFeature) {
                        ResidenceFeature::create([
                            'residence_id' => $residence->id,
                            'feature_id' => Features::RECEPTION_MANAGEMENT->value,
                            'is_active' => 1,
                        ]);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();

        return Command::SUCCESS;
    }
}
