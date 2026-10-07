<?php

namespace App\Support;

use App\Enums\Residence\ActivationStatusType;
use App\Enums\Residence\EntranceBarrierType;
use App\Enums\Residence\EntryLaneType;
use App\Enums\Residence\InternetProviderCompany;
use App\Enums\Residence\PropertyManagementType;
use App\Enums\Residence\SubType;
use App\Enums\Unit\StatusType;
use App\Enums\Vehicle\CarBodyType;
use App\Enums\Vehicle\FuelType;
use App\Enums\Vehicle\MotorcycleBodyType;
use Filament\Support\Colors\Color;

class WidgetColorPalette
{
    public static function neutralHex(): string
    {
        return '#64748B';
    }

    public static function whiteHex(): string
    {
        return '#FFFFFF';
    }

    public static function blueHex(): string
    {
        return '#3B82F6';
    }

    public static function emeraldHex(): string
    {
        return '#10B981';
    }

    public static function amberHex(): string
    {
        return '#F59E0B';
    }

    /** Four ordered colors for the visitor summary stats (In / Out / Remaining / Overnight). */
    public static function visitorStats(): array
    {
        return ['#10B981', '#06B6D4', '#F59E0B', '#EF4444'];
    }

    /** Primary color for a visitor trend line chart. */
    public static function trendLine(): string
    {
        return '#10B981';
    }

    /** Fill color (semi-transparent) for a visitor trend area chart. */
    public static function trendLineFill(): string
    {
        return 'rgba(16,185,129,0.1)';
    }

    public static function statColorFromHex(string $hex): array
    {
        // Map well-known hex values to exact Filament built-in colors so stats
        // and charts render the identical shade (Color::hex() generates an
        // approximate palette where shade-500 may drift from the source hex).
        $map = [
            '#F97316' => Color::Orange,
            '#3B82F6' => Color::Blue,
            '#A855F7' => Color::Purple,
            '#EF4444' => Color::Red,
            '#64748B' => Color::Slate,
            '#10B981' => Color::Emerald,
            '#06B6D4' => Color::Cyan,
            '#F59E0B' => Color::Amber,
        ];

        return $map[strtoupper($hex)] ?? $map[$hex] ?? Color::hex($hex);
    }

    public static function visitorType(): array
    {
        return [
            '#3B82F6',
            '#10B981',
            '#F97316',
        ];
    }

    public static function visitorVehicleType(): array
    {
        return [
            '#3B82F6',
            '#EF4444',
            '#10B981',
            '#F97316',
            '#06B6D4',
            '#A855F7',
        ];
    }

    public static function visitorPurpose(): array
    {
        return [
            '#3B82F6',
            '#10B981',
            '#F97316',
            '#06B6D4',
            '#EF4444',
            '#A855F7',
            '#64748B',
            '#F472B6',
            '#84CC16',
            '#FB923C',
        ];
    }

    public static function visitorParcelCourier(): array
    {
        return [
            '#EF4444', '#10B981', '#F97316', '#3B82F6', '#06B6D4', '#A855F7',
            '#64748B', '#FB7185', '#F472B6', '#E879F9', '#C084FC', '#A78BFA',
            '#818CF8', '#60A5FA', '#38BDF8', '#22D3EE', '#2DD4BF', '#34D399',
            '#4ADE80', '#84CC16', '#FACC15', '#FB923C',
        ];
    }

    public static function visitorFoodDelivery(): array
    {
        return [
            '#10B981', '#EF4444', '#F97316', '#3B82F6', '#06B6D4', '#A855F7',
            '#64748B', '#FB7185', '#F472B6', '#E879F9', '#C084FC', '#A78BFA',
            '#818CF8', '#60A5FA', '#38BDF8', '#22D3EE', '#2DD4BF', '#34D399',
            '#4ADE80', '#84CC16', '#FACC15', '#FB923C',
        ];
    }

    /** @return array<int, array{label: string, color: string}> */
    public static function propertyManagementTypes(): array
    {
        return collect(PropertyManagementType::cases())
            ->mapWithKeys(fn (PropertyManagementType $type) => [
                $type->value => ['label' => $type->getShortLabel(), 'color' => $type->getHex()],
            ])
            ->all();
    }

    /** @return array<int, string> */
    public static function ddiSubTypeColors(): array
    {
        return collect(SubType::publicOptions())
            ->mapWithKeys(fn ($label, int $value) => [$value => SubType::from($value)->getHex()])
            ->all();
    }

    public static function ddiSubTypeHex(int $subType): string
    {
        return static::ddiSubTypeColors()[$subType] ?? static::neutralHex();
    }

    /** @return array<int, string> */
    public static function ddiActivationStatusColors(): array
    {
        return collect(ActivationStatusType::cases())
            ->mapWithKeys(fn (ActivationStatusType $case) => [$case->value => $case->getHex()])
            ->all();
    }

    public static function ddiActivationStatusHex(int $statusId): string
    {
        return static::ddiActivationStatusColors()[$statusId] ?? static::neutralHex();
    }

    public static function ddiResidentShareColors(): array
    {
        return [
            'residents' => '#3B82F6',
            'potential' => '#F59E0B',
        ];
    }

    public static function residenceInfoEntryNumberColors(): array
    {
        return [
            1 => '#3B82F6',
            2 => '#4CAF50',
            3 => '#F59E0B',
            4 => '#EF4444',
            5 => '#9333EA',
            'default' => static::neutralHex(),
            'null' => static::neutralHex(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function residenceInfoEntryNumberSeriesColors(): array
    {
        return [
            '#3B82F6', '#4CAF50', '#FFC107', '#FF5722',
            '#9C27B0', '#673AB7', '#3F51B5', '#00BCD4',
            '#8BC34A', '#CDDC39', '#FF9800', '#F44336',
        ];
    }

    public static function residenceInfoGuardHouseLaneTypeColors(): array
    {
        return [
            EntryLaneType::SINGLE_LANE->value => '#3B82F6',
            EntryLaneType::DUAL_LANE->value => '#4CAF50',
            EntryLaneType::SAME_LANE->value => '#FFC107',
            'null' => static::neutralHex(),
        ];
    }

    public static function residenceInfoGuardHouseRoofColors(): array
    {
        return [
            'has_roof' => '#4ADE80',
            'no_roof' => '#3B82F6',
            'null' => static::neutralHex(),
        ];
    }

    public static function residenceInfoEntranceBarrierTypeColors(): array
    {
        return [
            EntranceBarrierType::GATE_FENCE->value => '#60A5FA',
            EntranceBarrierType::LPR->value => '#F87171',
            EntranceBarrierType::RFID->value => '#FBBF24',
            EntranceBarrierType::MANUAL_BARRIER->value => '#34D399',
            EntranceBarrierType::NO_BARRIER->value => '#A78BFA',
            'null' => '#9CA3AF',
        ];
    }

    public static function residenceInfoInternetProviderColors(): array
    {
        return [
            InternetProviderCompany::NT_TELECOM->value => '#3B82F6',
            InternetProviderCompany::TRUE_ONLINE->value => '#F59E0B',
            InternetProviderCompany::AIS_FIBRE->value => '#10B981',
            'not_set' => static::neutralHex(),
        ];
    }

    public static function residenceInfoCctvPresenceColors(): array
    {
        return [
            'yes' => '#4ADE80',
            'no' => '#3B82F6',
            'not_set' => static::neutralHex(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function residenceInfoCctvRangeColors(): array
    {
        return [
            '#3B82F6', '#EF4444', '#F59E0B', '#10B981', '#9333EA',
            '#06B6D4', '#E11D48', '#8B5CF6', '#22C55E', '#F97316',
        ];
    }

    public static function unitHouseTypeColors(): array
    {
        return [
            1 => '#3B82F6',
            2 => '#10B981',
            3 => '#F59E0B',
            4 => '#9333EA',
            5 => '#6366F1',
            6 => '#14B8A6',
            7 => '#0EA5E9',
            8 => '#C084FC',
            9 => '#F43F5E',
            10 => '#EC4899',
            11 => '#D946EF',
            12 => '#E879F9',
            13 => '#EF4444',
            14 => '#FB923C',
            15 => '#F97316',
            16 => '#8B5CF6',
            17 => '#7C3AED',
            18 => '#7DD3FC',
            19 => '#38BDF8',
            20 => '#0EA5E9',
            21 => '#0284C7',
            22 => '#0369A1',
            23 => '#5EEAD4',
            24 => '#2DD4BF',
            25 => '#14B8A6',
            26 => '#0D9488',
            27 => '#0F766E',
            'null' => '#9CA3AF',
        ];
    }

    public static function unitSignUpColors(): array
    {
        return [
            'signed_up' => static::emeraldHex(),
            'not_signed_up' => '#EF4444',
        ];
    }

    public static function unitOwnerTenantColors(): array
    {
        return [
            'owner' => static::emeraldHex(),
            'tenant' => static::blueHex(),
        ];
    }

    public static function unitStatusColors(): array
    {
        return [
            StatusType::Occupied->value => static::emeraldHex(),
            StatusType::OccupiedTenant->value => static::blueHex(),
            StatusType::Abandoned->value => static::amberHex(),
            StatusType::Not_yet_sold->value => '#6B7280',
            StatusType::NPA_NPL->value => '#EF4444',
            'null' => '#9CA3AF',
        ];
    }

    public static function unitUserNotSetHex(): string
    {
        return '#9CA3AF';
    }

    public static function unitUserGenderColors(): array
    {
        return [
            'male' => static::blueHex(),
            'female' => '#EC4899',
            'not_set' => static::unitUserNotSetHex(),
        ];
    }

    public static function unitUserOwnerTenantColors(): array
    {
        return [
            'owner' => static::emeraldHex(),
            'tenant' => static::amberHex(),
        ];
    }

    public static function unitUserActivationStatusColors(): array
    {
        return [
            'active' => static::blueHex(),
            'inactive' => static::amberHex(),
        ];
    }

    public static function unitUserAgeGroupColors(): array
    {
        return [
            '06-12' => '#3B82F6',
            '13-22' => '#10B981',
            '23-30' => '#F59E0B',
            '31-40' => '#9333EA',
            '41-50' => '#EF4444',
            '51-60' => '#E11D48',
            '61-70' => '#14B8A6',
            '>70' => '#6366F1',
            'Not Set' => static::unitUserNotSetHex(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function unitUserCountrySeriesColors(): array
    {
        return [
            '#3B82F6', '#10B981', '#F59E0B', '#9333EA', '#EF4444',
            '#E11D48', '#14B8A6', '#6366F1', '#F97316', '#84CC16',
        ];
    }

    public static function unitUserCreationTrendHex(): string
    {
        return '#4f46e5';
    }

    public static function unitHouseTypeHex(int|string|null $houseType): string
    {
        $key = $houseType ?? 'null';

        return static::unitHouseTypeColors()[$key] ?? static::neutralHex();
    }

    public static function committeeRoles(): array
    {
        return [
            1 => '#059669', // PRESIDENT (green)
            2 => '#2563EB', // VICE_PRESIDENT (blue)
            3 => '#0cbaea', // SECRETARY (orange)
            4 => '#7C3AED', // TREASURER (purple)
            5 => '#DC2626', // MEMBER (red)
        ];
    }

    public static function petTypeColors(): array
    {
        return [
            'dog' => '#3B82F6',
            'cat' => '#F59E0B',
            'not_set' => '#6B7280',
        ];
    }

    public static function petCreationTrendColors(): array
    {
        return [
            'dog' => '#3B82F6',
            'cat' => '#F59E0B',
        ];
    }

    public static function petAgeColors(): array
    {
        return [
            'lt_2' => '#3B82F6',
            'lt_4' => '#10B981',
            'lt_6' => '#F59E0B',
            'lt_8' => '#9333EA',
            'lt_10' => '#EF4444',
            'lt_12' => '#E11D48',
            'lt_14' => '#14B8A6',
            'lt_16' => '#6366F1',
            'lt_18' => '#F97316',
            'lt_20' => '#84CC16',
            'not_set' => '#6B7280',
        ];
    }

    public static function vehicleTypeColors(): array
    {
        return [
            'car' => '#3B82F6',
            'motorcycle' => '#EF4444',
        ];
    }

    public static function vehicleCreationTrendColors(): array
    {
        return [
            'car' => '#3B82F6',
            'motorcycle' => '#F59E0B',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function vehicleBrandSeriesColors(): array
    {
        return [
            '#3B82F6', '#10B981', '#F59E0B', '#9333EA', '#EF4444',
            '#E11D48', '#14B8A6', '#6366F1', '#F97316', '#84CC16',
            '#06B6D4', '#D946EF', '#F43F5E', '#FACC15', '#4ADE80',
            '#7C3AED', '#3F6212', '#C026D3', '#DB2777', '#475569',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function vehicleInsuranceSeriesColors(): array
    {
        return [
            '#3B82F6', '#10B981', '#F59E0B', '#9333EA', '#EF4444',
            '#E11D48', '#14B8A6', '#6366F1', '#F97316', '#84CC16',
        ];
    }

    public static function carFuelTypeColors(): array
    {
        return [
            FuelType::GASOLINE->value => '#3B82F6',
            FuelType::DIESEL->value => '#F59E0B',
            FuelType::PHEV->value => '#10B981',
            FuelType::EV->value => '#9333EA',
            'null' => '#6B7280',
        ];
    }

    public static function carBodyTypeColors(): array
    {
        return [
            CarBodyType::SEDAN->value => '#3B82F6',
            CarBodyType::SUV->value => '#F59E0B',
            CarBodyType::MPV->value => '#10B981',
            CarBodyType::PICKUP->value => '#9333EA',
            CarBodyType::VAN->value => '#EF4444',
            CarBodyType::PPV->value => '#6B7280',
            'null' => '#9CA3AF',
        ];
    }

    public static function motorcycleBodyTypeColors(): array
    {
        return [
            MotorcycleBodyType::UNDERBONE->value => '#3B82F6',
            MotorcycleBodyType::SCOOTER->value => '#F59E0B',
            MotorcycleBodyType::SPORT->value => '#10B981',
            MotorcycleBodyType::TOURING->value => '#9333EA',
            MotorcycleBodyType::OFF_ROAD->value => '#EF4444',
            MotorcycleBodyType::CRUISER->value => '#6B7280',
            MotorcycleBodyType::STANDARD->value => '#9CA3AF',
            'null' => '#D1D5DB',
        ];
    }

    public static function vehicleLineFill(string $hex, float $alpha = 0.4): string
    {
        $sanitizedHex = ltrim($hex, '#');

        if (strlen($sanitizedHex) !== 6) {
            return 'rgba(0,0,0,0.4)';
        }

        $red = hexdec(substr($sanitizedHex, 0, 2));
        $green = hexdec(substr($sanitizedHex, 2, 2));
        $blue = hexdec(substr($sanitizedHex, 4, 2));

        return sprintf('rgba(%d,%d,%d,%.2F)', $red, $green, $blue, $alpha);
    }

    public static function vehicleAge(): array
    {
        return [
            '1-5 years' => '#3B82F6',
            '6-10 years' => '#F59E0B',
            '11-15 years' => '#10B981',
            '16-20 years' => '#9333EA',
            '21-25 years' => '#EF4444',
            '26-30 years' => '#0F766E',
            '31-35 years' => '#0EA5E9',
            '36-40 years' => '#EAB308',
            '41-45 years' => '#22C55E',
            '46-50 years' => '#A855F7',
            '50+ years' => '#DC2626',
            'Not Yet Set' => '#9CA3AF',
        ];
    }

    public static function parcelStatusColors(): array
    {
        return [
            'today' => '#3B82F6',
            'picked_up' => '#10B981',
            'pending' => '#F59E0B',
            'not_my_parcel' => '#EF4444',
        ];
    }

    public static function parcelCreationTrendHex(): string
    {
        return '#6366F1';
    }

    /** @return array<int, string> */
    public static function parcelCourierSeriesColors(): array
    {
        return [
            '#3B82F6', '#10B981', '#F59E0B', '#9333EA', '#EF4444',
            '#6366F1', '#14B8A6', '#F97316', '#84CC16', '#E11D48',
        ];
    }
}
