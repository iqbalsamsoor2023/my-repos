<?php

namespace App\Console\Commands\OneTime;

use ValueError;
use App\Enums\Residence\SubType;
use App\Enums\Unit\HouseType;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateUnitsHouseType extends Command
{
    protected $signature = 'units:update-house-type';

    protected $description = 'Update units with default house_type based on residence sub_type';

    public function handle(): void
    {
        $this->info('Starting house_type update process...');

        // Get all sub_types that have default house_type mappings
        $mappedSubTypes = array_filter(
            SubType::cases(),
            fn ($subType) => HouseType::defaultBySubType($subType) !== null
        );

        if (empty($mappedSubTypes)) {
            $this->error('No valid sub_type to house_type mappings found!');

            return;
        }

        $query = Unit::with('residence')
            ->whereHas('residence', function ($q) use ($mappedSubTypes) {
                $q->whereNotNull('sub_type')
                    ->whereIn('sub_type', array_column($mappedSubTypes, 'value'));
            })
            ->where(function ($q) {
                $q->whereNull('house_type')
                    ->orWhereNotIn('house_type', function ($query) {
                        // This subquery gets the default house_type for each residence's sub_type
                        $query->select(DB::raw(
                            $this->buildCaseStatement()
                        ))
                            ->from('residences')
                            ->whereColumn('residences.id', 'units.residence_id');
                    });
            });

        $count = $query->count();
        $this->info("Found {$count} units that may need updates...");

        if ($count === 0) {
            $this->info('No units require updates.');

            return;
        }

        $bar = $this->output->createProgressBar($count);
        $updated = 0;

        // Process in chunks for memory efficiency
        $query->chunkById(200, function ($units) use ($bar, &$updated) {
            foreach ($units as $unit) {
                try {
                    $subType = SubType::from($unit->residence->sub_type);
                    $defaultHouseType = HouseType::defaultBySubType($subType);

                    if ($defaultHouseType && $unit->house_type != $defaultHouseType->value) {
                        $unit->house_type = $defaultHouseType->value;
                        $unit->save();
                        $updated++;
                    }
                } catch (ValueError $e) {
                    $this->warn("Invalid sub_type value: {$unit->residence->sub_type} for unit {$unit->id}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("✅ Successfully updated {$updated} unit(s).");
    }

    /**
     * Build a SQL CASE statement that maps sub_type to default house_type
     */
    protected function buildCaseStatement(): string
    {
        $cases = [];
        foreach (SubType::cases() as $subType) {
            $default = HouseType::defaultBySubType($subType);
            if ($default) {
                $cases[] = "WHEN {$subType->value} THEN {$default->value}";
            }
        }

        return "CASE sub_type\n".implode("\n", $cases)."\nELSE NULL END";
    }
}
