<?php

namespace App\Enums\Unit;

use App\Enums\Residence\SubType;
use Filament\Support\Contracts\HasLabel;

enum HouseType: int implements HasLabel
{
    // Single Home
    case SINGLE_HOME_1_STOREY = 1;
    case SINGLE_HOME_2_STOREY = 2;
    case SINGLE_HOME_3_STOREY = 3;
    case SINGLE_HOME_4_STOREY = 4;

    // Twin Home
    case TWIN_HOME_1_STOREY = 5;
    case TWIN_HOME_2_STOREY = 6;
    case TWIN_HOME_3_STOREY = 7;
    case TWIN_HOME_4_STOREY = 8;

    // Pool Villa
    case POOL_VILLA_1_STOREY = 9;
    case POOL_VILLA_2_STOREY = 10;
    case POOL_VILLA_3_STOREY = 11;
    case POOL_VILLA_4_STOREY = 12;

    // Town Home
    case TOWN_HOME_1_STOREY = 13;
    case TOWN_HOME_2_STOREY = 14;
    case TOWN_HOME_3_STOREY = 15;

    // Home Office
    case HOME_OFFICE_4_STOREY = 16;
    case HOME_OFFICE_5_STOREY = 17;

    // Condo - Low Rise
    case CONDO_LOWRISE_STUDIO = 18;
    case CONDO_LOWRISE_1_BEDROOM = 19;
    case CONDO_LOWRISE_2_BEDROOM = 20;
    case CONDO_LOWRISE_3_BEDROOM = 21;
    case CONDO_LOWRISE_DUPLEX = 22;

    // Condo - High Rise
    case CONDO_HIGHRISE_STUDIO = 23;
    case CONDO_HIGHRISE_1_BEDROOM = 24;
    case CONDO_HIGHRISE_2_BEDROOM = 25;
    case CONDO_HIGHRISE_3_BEDROOM = 26;
    case CONDO_HIGHRISE_DUPLEX = 27;

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::SINGLE_HOME_1_STOREY => __('unit.single_home_storey', ['count' => 1]),
            self::SINGLE_HOME_2_STOREY => __('unit.single_home_storey', ['count' => 2]),
            self::SINGLE_HOME_3_STOREY => __('unit.single_home_storey', ['count' => 3]),
            self::SINGLE_HOME_4_STOREY => __('unit.single_home_storey', ['count' => 4]),

            self::TWIN_HOME_1_STOREY => __('unit.twin_home_storey', ['count' => 1]),
            self::TWIN_HOME_2_STOREY => __('unit.twin_home_storey', ['count' => 2]),
            self::TWIN_HOME_3_STOREY => __('unit.twin_home_storey', ['count' => 3]),
            self::TWIN_HOME_4_STOREY => __('unit.twin_home_storey', ['count' => 4]),

            self::POOL_VILLA_1_STOREY => 'Pool Villa - 1 Storey',
            self::POOL_VILLA_2_STOREY => 'Pool Villa - 2 Storey',
            self::POOL_VILLA_3_STOREY => 'Pool Villa - 3 Storey',
            self::POOL_VILLA_4_STOREY => 'Pool Villa - 4 Storey',

            self::TOWN_HOME_1_STOREY => __('unit.town_home_storey', ['count' => 1]),
            self::TOWN_HOME_2_STOREY => __('unit.town_home_storey', ['count' => 2]),
            self::TOWN_HOME_3_STOREY => __('unit.town_home_storey', ['count' => 3]),

            self::HOME_OFFICE_4_STOREY => __('unit.home_office_storey', ['count' => 4]),
            self::HOME_OFFICE_5_STOREY => __('unit.home_office_storey', ['count' => 5]),

            self::CONDO_LOWRISE_STUDIO => __('unit.condo').' - '.__('unit.low_rise').' - '.__('unit.studio'),
            self::CONDO_LOWRISE_1_BEDROOM => __('unit.condo').' - '.__('unit.low_rise').' - 1 '.__('unit.bedroom'),
            self::CONDO_LOWRISE_2_BEDROOM => __('unit.condo').' - '.__('unit.low_rise').' - 2 '.__('unit.bedroom'),
            self::CONDO_LOWRISE_3_BEDROOM => __('unit.condo').' - '.__('unit.low_rise').' - 3 '.__('unit.bedroom'),
            self::CONDO_LOWRISE_DUPLEX => __('unit.condo').' - '.__('unit.low_rise').' - '.__('unit.duplex'),

            self::CONDO_HIGHRISE_STUDIO => __('unit.condo').' - '.__('unit.high_rise').' - '.__('unit.studio'),
            self::CONDO_HIGHRISE_1_BEDROOM => __('unit.condo').' - '.__('unit.high_rise').' - 1 '.__('unit.bedroom'),
            self::CONDO_HIGHRISE_2_BEDROOM => __('unit.condo').' - '.__('unit.high_rise').' - 2 '.__('unit.bedroom'),
            self::CONDO_HIGHRISE_3_BEDROOM => __('unit.condo').' - '.__('unit.high_rise').' - 3 '.__('unit.bedroom'),
            self::CONDO_HIGHRISE_DUPLEX => __('unit.condo').' - '.__('unit.high_rise').' - '.__('unit.duplex'),
        };
    }

    public static function options(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public function subType(): SubType
    {
        return match ($this) {
            self::SINGLE_HOME_1_STOREY,
            self::SINGLE_HOME_2_STOREY,
            self::SINGLE_HOME_3_STOREY,
            self::SINGLE_HOME_4_STOREY => SubType::SINGLE_HOME,

            self::TWIN_HOME_1_STOREY,
            self::TWIN_HOME_2_STOREY,
            self::TWIN_HOME_3_STOREY,
            self::TWIN_HOME_4_STOREY => SubType::TWIN_HOME,

            self::POOL_VILLA_1_STOREY,
            self::POOL_VILLA_2_STOREY,
            self::POOL_VILLA_3_STOREY,
            self::POOL_VILLA_4_STOREY => SubType::POOL_VILLA,

            self::TOWN_HOME_1_STOREY,
            self::TOWN_HOME_2_STOREY,
            self::TOWN_HOME_3_STOREY => SubType::TOWN_HOME,

            self::HOME_OFFICE_4_STOREY,
            self::HOME_OFFICE_5_STOREY => SubType::HOME_OFFICE,

            self::CONDO_LOWRISE_STUDIO,
            self::CONDO_LOWRISE_1_BEDROOM,
            self::CONDO_LOWRISE_2_BEDROOM,
            self::CONDO_LOWRISE_3_BEDROOM,
            self::CONDO_LOWRISE_DUPLEX => SubType::CONDO_LOW_RISE,

            self::CONDO_HIGHRISE_STUDIO,
            self::CONDO_HIGHRISE_1_BEDROOM,
            self::CONDO_HIGHRISE_2_BEDROOM,
            self::CONDO_HIGHRISE_3_BEDROOM,
            self::CONDO_HIGHRISE_DUPLEX => SubType::CONDO_HIGH_RISE,
        };
    }

    public static function bySubType(SubType $subType): array
    {
        return array_filter(self::cases(), fn ($case) => $case->subType() === $subType);
    }

    public static function defaultBySubType(SubType $subType): ?self
    {
        return match ($subType) {
            SubType::SINGLE_HOME => self::SINGLE_HOME_2_STOREY,
            SubType::TWIN_HOME => self::TWIN_HOME_2_STOREY,
            SubType::POOL_VILLA => self::POOL_VILLA_1_STOREY,
            SubType::TOWN_HOME => self::TOWN_HOME_2_STOREY,
            SubType::HOME_OFFICE => self::HOME_OFFICE_4_STOREY,
            SubType::CONDO_LOW_RISE => self::CONDO_LOWRISE_1_BEDROOM,
            SubType::CONDO_HIGH_RISE => self::CONDO_HIGHRISE_1_BEDROOM,
            default => null,
        };
    }
}
