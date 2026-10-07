<?php

namespace App\Enums\Residence;

use App\Support\WidgetColorPalette;
use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SubType: int implements HasColor, HasIcon, HasLabel
{
    case POOL_VILLA = 1;
    case SINGLE_HOME = 2;
    case TWIN_HOME = 3;
    case TOWN_HOME = 4;
    case HOME_OFFICE = 5;
    case CONDO_HIGH_RISE = 6;
    case CONDO_LOW_RISE = 7;

    case SERVICE_APARTMENT = 8;
    case DORMITORY = 9;

    case GOVERNMENT_OFFICE = 10;
    case HOTEL = 11;
    case OFFICE_BUILDING = 12;
    case SHOPPING_MALL = 13;
    case HOSPITAL = 14;
    case SCHOOL = 15;
    case SHOWROOM = 16;
    case RELIGIOUS_ORGANIZATION = 17;
    case ART_GALLERY = 18;
    case SPORT_CLUB = 19;

    case LOGISTIC_CENTER = 20;
    case RESTAURANT = 21;
    case STATION_TRAIN = 22;
    case STATION_BUS = 23;
    case STATION_BTS_OR_MRT = 24;
    case TERMINAL_FERRY_OR_PORTS = 25;
    case AIRPORT = 26;
    case UNIVERSITY = 27;
    case EXHIBITION_HALL = 28;
    case PARKING_BUILDING = 29;
    case BANK = 30;
    case STADIUM = 31;
    case MUSEUM = 32;
    case THEME_PARK_OR_ZOO = 33;

    public function getLabel(): string
    {
        return match ($this) {
            self::POOL_VILLA => __('residence.subtypes.pool_villa'),
            self::SINGLE_HOME => __('residence.subtypes.single_home'),
            self::TWIN_HOME => __('residence.subtypes.twin_home'),
            self::TOWN_HOME => __('residence.subtypes.town_home'),
            self::HOME_OFFICE => __('residence.subtypes.home_office'),
            self::CONDO_HIGH_RISE => __('residence.subtypes.condo_high_rise'),
            self::CONDO_LOW_RISE => __('residence.subtypes.condo_low_rise'),
            self::SERVICE_APARTMENT => __('residence.subtypes.service_apartment'),
            self::DORMITORY => __('residence.subtypes.dormitory'),
            self::GOVERNMENT_OFFICE => __('residence.subtypes.government_office'),
            self::HOTEL => __('residence.subtypes.hotel'),
            self::OFFICE_BUILDING => __('residence.subtypes.office_building'),
            self::SHOPPING_MALL => __('residence.subtypes.shopping_mall'),
            self::HOSPITAL => __('residence.subtypes.hospital'),
            self::SCHOOL => __('residence.subtypes.school'),
            self::SHOWROOM => __('residence.subtypes.showroom'),
            self::RELIGIOUS_ORGANIZATION => __('residence.subtypes.religious_organization'),
            self::ART_GALLERY => __('residence.subtypes.art_gallery'),
            self::SPORT_CLUB => __('residence.subtypes.sport_club'),
            self::LOGISTIC_CENTER => __('residence.subtypes.logistic_center'),
            self::RESTAURANT => __('residence.subtypes.restaurant'),
            self::STATION_TRAIN => __('residence.subtypes.station_train'),
            self::STATION_BUS => __('residence.subtypes.station_bus'),
            self::STATION_BTS_OR_MRT => __('residence.subtypes.station_bts_or_mrt'),
            self::TERMINAL_FERRY_OR_PORTS => __('residence.subtypes.terminal_ferry_or_ports'),
            self::AIRPORT => __('residence.subtypes.airport'),
            self::UNIVERSITY => __('residence.subtypes.university'),
            self::EXHIBITION_HALL => __('residence.subtypes.exhibition_hall'),
            self::PARKING_BUILDING => __('residence.subtypes.parking_building'),
            self::BANK => __('residence.subtypes.bank'),
            self::STADIUM => __('residence.subtypes.stadium'),
            self::MUSEUM => __('residence.subtypes.museum'),
            self::THEME_PARK_OR_ZOO => __('residence.subtypes.theme_park_or_zoo'),
        };
    }

    public function getLabelWithCategory(): string
    {
        $category = match ($this) {
            self::POOL_VILLA, self::SINGLE_HOME, self::TWIN_HOME,
            self::TOWN_HOME, self::HOME_OFFICE, self::CONDO_HIGH_RISE,
            self::CONDO_LOW_RISE => 'Public',

            self::SERVICE_APARTMENT, self::DORMITORY => 'Residence',

            default => 'PMOC',
        };

        return "{$category} - ".$this->getLabel();
    }

    private const CATEGORY_CODE_LENGTH = 2;

    public function getPublicCategoryCode(): string
    {
        return str_pad((string) $this->value, self::CATEGORY_CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    public function getHex(): string
    {
        return match ($this) {
            self::POOL_VILLA => '#EF4444',
            self::SINGLE_HOME => '#3B82F6',
            self::TWIN_HOME => '#F97316',
            self::TOWN_HOME => '#10B981',
            self::HOME_OFFICE => '#8B5CF6',
            self::CONDO_HIGH_RISE => '#EC4899',
            self::CONDO_LOW_RISE => '#06B6D4',
            default => WidgetColorPalette::neutralHex(),
        };
    }

    public function getColor(): string|array
    {
        return WidgetColorPalette::statColorFromHex($this->getHex());
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::POOL_VILLA => 'heroicon-o-home-modern',
            self::SINGLE_HOME => 'heroicon-o-home',
            self::TWIN_HOME => 'heroicon-o-building-office',
            self::TOWN_HOME => 'heroicon-o-building-office-2',
            self::HOME_OFFICE => 'heroicon-o-building-library',
            self::CONDO_HIGH_RISE => 'heroicon-o-building-office',
            self::CONDO_LOW_RISE => 'heroicon-o-building-storefront',
            default => 'heroicon-o-home',
        };
    }

    public static function publicOptions(): array
    {
        return collect([
            self::POOL_VILLA,
            self::SINGLE_HOME,
            self::TWIN_HOME,
            self::TOWN_HOME,
            self::HOME_OFFICE,
            self::CONDO_HIGH_RISE,
            self::CONDO_LOW_RISE,
        ])->mapWithKeys(fn (SubType $subType) => [
            $subType->value => $subType->getLabel(),
        ])->toArray();
    }
}
