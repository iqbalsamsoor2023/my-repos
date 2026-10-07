<?php

namespace App\Console\Commands;

use App\Enums\Residence\Features;
use App\Models\Residence;
use App\Models\ResidenceFeature;
use Illuminate\Console\Command;

class EnableParkingFeeBasicForResidences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enable-parking-fee-basic-for-residences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update or create residences parking fee basic feature with active status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parkingFeeBasicFeatureId = Features::PARKING_FEE_BASIC->value;
        $residences = Residence::select('id')->get();
        $residenceFeatureRecords = ResidenceFeature::where('feature_id', $parkingFeeBasicFeatureId)->get();

        $bar = $this->output->createProgressBar(count($residences));
        $bar->start();

        Residence::select('id')->limit(400)->chunk(100, function ($chunkedResidences) use ($parkingFeeBasicFeatureId, $residenceFeatureRecords, $bar) {
            foreach ($chunkedResidences as $residence) {
                $existingResidenceFeatureRecord = $residenceFeatureRecords
                    ->where('residence_id', $residence->id)
                    ->first();

                if (empty($existingResidenceFeatureRecord)) {
                    ResidenceFeature::create([
                        'residence_id' => $residence->id,
                        'feature_id' => $parkingFeeBasicFeatureId,
                        'is_active' => 1,
                    ]);
                } else {
                    if (! $existingResidenceFeatureRecord->is_active) {
                        $existingResidenceFeatureRecord->is_active = 1;
                        $existingResidenceFeatureRecord->save();
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }
}
