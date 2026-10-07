<?php

namespace App\Console\Commands\DataMigrations\Mmb;

use App\Models\FacilityAndAmenity;
use App\Models\ResidenceAmenity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClaimableItemOthersOptionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:claimable-item-others-option-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate claimable_items "Others" data to claimable_titles (to support old data only)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $othersItems = [
            'Others',
            'อื่นๆ Other',
            'อื่นๆ (Other)',
            'Other Facilities',
            'อื่่น(other)',
            'Other (อื่นๆ)',
            'Others (อื่นๆ)',
            'อื่นๆ(Other)',
            'Others Public Area',
            'อื่นๆ Other',
            'อื่นๆ',
            'พื้นที่ส่วนกลางอื่นๆ',
            'อื่น ๆ',
        ];

        $query = DB::table('claimable_items')->whereIn('item', $othersItems)
            ->whereNull('deleted_at');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No matching claimable items found.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $claimableItems = $query->get();

        $facilityAndAmenity = FacilityAndAmenity::where('name', 'Others')->first();

        if (! $facilityAndAmenity) {
            $this->error('FacilityAndAmenity with name "Others" not found.');

            return Command::FAILURE;
        }

        foreach ($claimableItems as $claimableItem) {
            $exists = ResidenceAmenity::where('residence_id', $claimableItem->residence_id)
                ->where('facility_and_amenity_id', $facilityAndAmenity->id)
                ->exists();

            if (! $exists) {
                ResidenceAmenity::create([
                    'residence_id' => $claimableItem->residence_id,
                    'facility_and_amenity_id' => $facilityAndAmenity->id,
                    'is_active' => false,
                    'is_claimable' => false,
                    'created_at' => $claimableItem->created_at,
                    'updated_at' => $claimableItem->updated_at,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
