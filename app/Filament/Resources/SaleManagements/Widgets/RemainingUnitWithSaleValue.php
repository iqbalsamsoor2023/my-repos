<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\User\RoleType;
use App\Models\Unit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class RemainingUnitWithSaleValue extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.widgets.custom.triple-in-one-stats';
    
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
        'sm' => 1,
    ];

    protected function getViewData(): array
    {
        // get only RTM module enabled residence units
        $unitQuery = Unit::with(['saleAdvertisement'])
            ->whereHas('residence', function ($query) {
                $query->whereNotNull('sales_management_user_id');
            });

        // get the residence id list by user role
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $unitQuery->where('units.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            if (count($residenceIdList) > 0) {
                $unitQuery->whereIn('units.residence_id', $residenceIdList);
            }
        }

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $unitQuery->where('residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $unitQuery->where('id', $unitIdFilter);
        }

        $sellingStatusFilter = $this->tableFilters['selling_status']['values'] ?? [];
        if ($sellingStatusFilter) {
            $unitQuery->whereHas('saleAdvertisement', function ($query) use ($sellingStatusFilter) {
                $query->whereIn('selling_status', $sellingStatusFilter);
            });
        }

        $salePriceFromFilter = $this->tableFilters['sale_price_from']['sale_price_from'] ?? null;
        if ($salePriceFromFilter) {
            $unitQuery->whereHas('saleAdvertisement', function ($query) use ($salePriceFromFilter) {
                $query->where('sale_price', '>=', $salePriceFromFilter);
            });
        }

        $salePriceToFilter = $this->tableFilters['sale_price_to']['sale_price_to'] ?? null;
        if ($salePriceToFilter) {
            $unitQuery->whereHas('saleAdvertisement', function ($query) use ($salePriceToFilter) {
                $query->where('sale_price', '<=', $salePriceToFilter);
            });
        }
        // end applying table filters

        $totalUnits = $unitQuery->count();
        $unitsRemainingForSale = $unitQuery->where(function ($subQuery) {
            $subQuery->whereHas('saleAdvertisement', function ($query) {
                $query->availableForSale();
            });
        })->count();

        $valueOfRemainingSaleUnit = $unitQuery->get()->sum(function ($unit) {
            return $unit->saleAdvertisement ? $unit->saleAdvertisement->sale_price : 0;
        });

        return [
            'major' => [
                'label' => __('sale.total_units'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'green',
                'value' => $totalUnits,
            ],
            'minorTop' => [
                'label' => __('sale.units_remaining'),
                'icon' => 'heroicon-o-users',
                'color' => '#3B82F6',
                'value' => $unitsRemainingForSale,
            ],
            'minorBottom' => [
                'label' => __('sale.value_of_sale_units'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'orange',
                'value' => Number::format($valueOfRemainingSaleUnit),
            ],
        ];
    }
}
