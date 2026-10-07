<?php

namespace App\Enums\Residence;

use App\Models\Residence;
use Filament\Support\Contracts\HasLabel;

enum MoobanType: int implements HasLabel
{
    case PUBLIC = 1;
    case RESIDENCE = 2;
    case FACTORY = 3;
    case PMOC = 4;
    case DEMO_FOR_SG = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PUBLIC => __('residence.mooban_types.public'),
            self::RESIDENCE => __('residence.mooban_types.residence'),
            self::FACTORY => __('residence.mooban_types.factory'),
            self::PMOC => __('residence.mooban_types.pmoc'),
            self::DEMO_FOR_SG => __('residence.mooban_types.demo_for_sg'),
        };
    }

    public function allowedSubTypes(): array
    {
        return match ($this) {
            self::PUBLIC => [
                SubType::POOL_VILLA,
                SubType::SINGLE_HOME,
                SubType::TWIN_HOME,
                SubType::TOWN_HOME,
                SubType::HOME_OFFICE,
                SubType::CONDO_HIGH_RISE,
                SubType::CONDO_LOW_RISE,
            ],
            self::RESIDENCE => [
                SubType::SERVICE_APARTMENT,
                SubType::DORMITORY,
            ],
            self::PMOC => [
                SubType::GOVERNMENT_OFFICE,
                SubType::HOTEL,
                SubType::OFFICE_BUILDING,
                SubType::SHOPPING_MALL,
                SubType::HOSPITAL,
                SubType::SCHOOL,
                SubType::SHOWROOM,
                SubType::RELIGIOUS_ORGANIZATION,
                SubType::ART_GALLERY,
                SubType::SPORT_CLUB,

                SubType::LOGISTIC_CENTER,
                SubType::RESTAURANT,
                SubType::STATION_TRAIN,
                SubType::STATION_BUS,
                SubType::STATION_BTS_OR_MRT,
                SubType::TERMINAL_FERRY_OR_PORTS,
                SubType::AIRPORT,
                SubType::UNIVERSITY,
                SubType::EXHIBITION_HALL,
                SubType::PARKING_BUILDING,
                SubType::BANK,
                SubType::STADIUM,
                SubType::MUSEUM,
                SubType::THEME_PARK_OR_ZOO,
            ],
            default => [],
        };
    }

    public static function casesToOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->toArray();
    }

    public static function isSubTypeVisible($get, $record): bool
    {
        $moobanType = $get('residence_id')
            ? Residence::find($get('residence_id'))?->mooban_type
            : ($record?->residence?->mooban_type ?? null);

        return in_array($moobanType, [
            self::PUBLIC->value,
            self::RESIDENCE->value,
            self::PMOC->value,
        ]);
    }
}
