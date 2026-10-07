<?php

namespace App\Console\Commands;

use App\Enums\Residence\MoobanType;
use App\Enums\Unit\PmocType;
use App\Enums\Unit\PropertyType;
use App\Models\Residence;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResidenceV2DataMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residence:data-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate residence 2.0 data';

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
                if (count($residence->units) > 0) {
                    $moobanType = $residence->mooban_type;
                    $pmocType = $residence->pmoc_type;
                    $unitPropertyType = 0;

                    if ($residence->mooban_type == 'Condo') {
                        $unitPropertyType = PropertyType::CONDO_HIGH_RISE->value;
                        $pmocType = null;

                        if ($residence->pmoc_type == 1) {
                            $moobanType = Str::title(MoobanType::PUBLIC->name);
                        } else {
                            $moobanType = Str::title(MoobanType::RESIDENCE->name);
                        }
                    } elseif ($residence->mooban_type == 'Town home') {
                        $unitPropertyType = PropertyType::TOWN_HOME->value;
                        $pmocType = null;

                        if ($residence->pmoc_type == 1) {
                            $moobanType = Str::title(MoobanType::PUBLIC->name);
                        } else {
                            $moobanType = Str::title(MoobanType::RESIDENCE->name);
                        }
                    } elseif ($residence->mooban_type == 'Single home') {
                        $unitPropertyType = PropertyType::SINGLE_HOME->value;
                        $pmocType = null;

                        if ($residence->pmoc_type == 1) {
                            $moobanType = Str::title(MoobanType::PUBLIC->name);
                        } else {
                            $moobanType = Str::title(MoobanType::RESIDENCE->name);
                        }
                    } elseif ($residence->mooban_type == 'Factory') {
                        $pmocType = null;
                    } elseif ($residence->mooban_type == 'Office building') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::OFFICE_BUILDING->value;
                    } elseif ($residence->mooban_type == "Community's mall" || $residence->mooban_type == 'Community mall') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::COMMUNITY_MALL->value;
                    } elseif ($residence->mooban_type == 'Hospital') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::HOSPITAL->value;
                    } elseif ($residence->mooban_type == 'Hotel') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::HOTEL->value;
                    } elseif ($residence->mooban_type == 'Art Gallery') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::ART_GALLERY->value;
                    } elseif ($residence->mooban_type == 'Sport Club') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::SPORT_CLUB->value;
                    } elseif ($residence->mooban_type == 'School') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::SCHOOL->value;
                    } elseif ($residence->mooban_type == 'Showroom') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::SHOWROOM->value;
                    } elseif ($residence->mooban_type == 'Religious organization') {
                        $moobanType = MoobanType::PMOC->name;
                        $pmocType = PmocType::RELIGIOUS_ORGANIZATION->value;
                    }

                    foreach ($residence->units as $unit) {
                        $unit->update([
                            'property_type' => $unitPropertyType,
                        ]);
                    }

                    $residence->update([
                        'mooban_type' => $moobanType,
                        'pmoc_type' => $pmocType,
                    ]);
                } else {
                    if ($residence->mooban_type == 'Condo' || $residence->mooban_type == 'Town home' || $residence->mooban_type == 'Single home') {
                        if ($residence->pmoc_type == 1) {
                            $moobanType = Str::title(MoobanType::PUBLIC->name);
                        } else {
                            $moobanType = Str::title(MoobanType::RESIDENCE->name);
                        }

                        $residence->update([
                            'mooban_type' => $moobanType,
                            'pmoc_type' => null,
                        ]);
                    } elseif ($residence->mooban_type == 'Factory') {
                        $residence->update([
                            'pmoc_type' => null,
                        ]);
                    } elseif ($residence->mooban_type == 'Office building') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::OFFICE_BUILDING->value,
                        ]);
                    } elseif ($residence->mooban_type == "Community's mall" || $residence->mooban_type == 'Community mall') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::COMMUNITY_MALL->value,
                        ]);
                    } elseif ($residence->mooban_type == 'Hospital') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::HOSPITAL->value,
                        ]);
                    } elseif ($residence->mooban_type == 'Hotel') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::HOTEL->value,
                        ]);
                    } elseif ($residence->mooban_type == 'Art Gallery') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::ART_GALLERY->value,
                        ]);
                    } elseif ($residence->mooban_type == 'Sport Club') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::SPORT_CLUB->value,
                        ]);
                    } elseif ($residence->mooban_type == 'School') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::SCHOOL->value,
                        ]);
                    } elseif ($residence->mooban_type == 'Showroom') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::SHOWROOM->value,
                        ]);
                    } elseif ($residence->mooban_type == 'Religious organization') {
                        $residence->update([
                            'mooban_type' => MoobanType::PMOC->name,
                            'pmoc_type' => PmocType::RELIGIOUS_ORGANIZATION->value,
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
