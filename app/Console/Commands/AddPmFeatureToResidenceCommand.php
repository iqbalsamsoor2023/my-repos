<?php

namespace App\Console\Commands;

use App\Enums\Residence\Features;
use App\Enums\User\RoleType;
use App\Helpers\Residence\AccountHelper;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddPmFeatureToResidenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residence:enable-pm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable Property Management feature and create accounts for all residences';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $featureId = Features::PROPERTY_MANAGEMENT->value;
        $roleTypePM = RoleType::PM->value;
        $roleTypePropertyManagement = RoleType::PROPERTY_MANAGEMENT->value;
        $totalResidences = Residence::count();

        if ($totalResidences == 0) {
            $this->info('No Residence found!');

            return;
        }

        $this->info("Processing {$totalResidences} residences...");

        $progressBar = $this->output->createProgressBar($totalResidences);
        $progressBar->start();

        Residence::chunkById(100, function ($residences) use ($featureId, $roleTypePM, $roleTypePropertyManagement, $progressBar) {
            $newData = [];

            foreach ($residences as $residence) {
                $exists = DB::table('residence_feature')
                    ->where('residence_id', $residence->id)
                    ->where('feature_id', $featureId)
                    ->where('is_active', true)
                    ->exists();

                if (! $exists) {
                    $newData[] = [
                        'residence_id' => $residence->id,
                        'feature_id' => $featureId,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Call the helper to create a Property Management account
                is_null($residence->property_management_user_id)
                ? AccountHelper::manageAccountCreation($residence, $roleTypePM, $roleTypePropertyManagement)
                : User::withTrashed()->findOrFail($residence->property_management_user_id)->restore();

                $progressBar->advance();
            }
            // Bulk insert new records if there are any
            if (! empty($newData)) {
                DB::table('residence_feature')->insert($newData);
            }
        });

        $progressBar->finish();
        $this->info("\nFeature ID {$featureId} added to residences successfully.");
    }
}
