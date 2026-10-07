<?php

namespace App\Filament\Resources\TenancyManagements\Widgets;

use App\Enums\User\RoleType;
use App\Models\Unit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TotalUnitWithTenancyValue extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.widgets.custom.triple-in-one-stats';
    
    protected int | string | array $columnSpan = 1;

    protected function getViewData(): array
    {
        // get only RTM module enabled residence units

        $unitQuery = Unit::with(['rentAdvertisement']);

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $unitQuery->where('units.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::RESALES_AND_TENANCY_MANAGEMENT->value)) {
            $residenceIds = get_residence_id_list_by_rtm($user->id);
            if ($residenceIds) {
                $unitQuery->whereIn('units.residence_id', $residenceIds);
            }
        }

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $unitQuery->where('units.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $unitQuery->where('units.id', $unitIdFilter);
        }

        $hasCustomRule = $this->tableFilters['has_custom_rule']['value'] ?? null;
        if ($hasCustomRule) {
            $unitQuery->whereHas('rentAdvertisement', function ($query) use ($hasCustomRule) {
                $query->where('has_custom_rule', $hasCustomRule);
            });
        }

        $isRentalUpfront = $this->tableFilters['is_rental_upfront']['value'] ?? null;
        if ($isRentalUpfront) {
            $unitQuery->whereHas('rentAdvertisement', function ($query) use ($isRentalUpfront) {
                $query->where('is_rental_upfront', $isRentalUpfront);
            });
        }

        $rentPriceFrom = $this->tableFilters['rent_price_from']['value'] ?? null;
        if ($rentPriceFrom) {
            $unitQuery->whereHas('rentAdvertisement', function ($query) use ($rentPriceFrom) {
                $query->where('rent_price', '>=', $rentPriceFrom);
            });
        }

        $rentPriceTo = $this->tableFilters['rent_price_to']['value'] ?? null;
        if ($rentPriceTo) {
            $unitQuery->whereHas('rentAdvertisement', function ($query) use ($rentPriceTo) {
                $query->where('rent_price', '<=', $rentPriceTo);
            });
        }

        $depositFrom = $this->tableFilters['deposit_from']['value'] ?? null;
        if ($depositFrom) {
            $unitQuery->whereHas('rentAdvertisement', function ($query) use ($depositFrom) {
                $query->where('deposit', '>=', $depositFrom);
            });
        }

        $depositTo = $this->tableFilters['deposit_to']['value'] ?? null;
        if ($depositTo) {
            $unitQuery->whereHas('rentAdvertisement', function ($query) use ($depositTo) {
                $query->where('deposit', '<=', $depositTo);
            });
        }
        // end applying table filters

        $totalUnits = $unitQuery->count();
        $unitsForRent = $unitQuery->where(function ($subQuery) {
            $subQuery->whereHas('rentAdvertisement', function ($query) {
                $query->availableForRent();
            });
        })->count();
        $valueOfRentUnits = $unitQuery->get()->sum(function ($unit) {
            return $unit->rentAdvertisement ? $unit->rentAdvertisement->rent_price : 0;
        });

        return [
            'major' => [
                'label' => __('resales-and-tenancies.total_units'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'green',
                'value' => $totalUnits,
            ],
            'minorTop' => [
                'label' => __('resales-and-tenancies.units_for_rent'),
                'icon' => 'heroicon-o-users',
                'color' => '#3B82F6',
                'value' => $unitsForRent,
            ],
            'minorBottom' => [
                'label' => __('resales-and-tenancies.value_of_rent_units'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'orange',
                'value' => Number::format($valueOfRentUnits),
            ],
        ];
    }
}
