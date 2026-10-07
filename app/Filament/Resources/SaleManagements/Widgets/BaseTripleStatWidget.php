<?php

namespace App\Filament\Resources\SaleManagements\Widgets;

use App\Enums\User\RoleType;
use App\Models\Unit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseTripleStatWidget extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.widgets.custom.triple-in-one-stats';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
        'sm' => 1,
    ];

    protected function getBaseUnitQuery(): Builder
    {
        $query = Unit::query()
            ->whereHas('residence', function($query) {
                $query->whereNotNull('sales_management_user_id');
            });

        // Apply user role filters
        $user = auth()->user();

        if ($user->hasAnyRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $query->where('units.residence_id', $residence->id);
            }
        } elseif ($user->hasRole(RoleType::SALES_MANAGEMENT->value)) {
            $residenceIdList = get_residence_id_list_by_sm($user->id);
            if (count($residenceIdList) > 0) {
                $query->whereIn('units.residence_id', $residenceIdList);
            }
        }

        // Apply table filters
        if ($residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null) {
            $query->where('residence_id', $residenceIdFilter);
        }

        if ($unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null) {
            $query->where('id', $unitIdFilter);
        }

        if ($sellingStatusFilter = $this->tableFilters['selling_status']['values'] ?? []) {
            $query->whereHas('saleAdvertisement', function($q) use ($sellingStatusFilter) {
                $q->whereIn('selling_status', $sellingStatusFilter);
            });
        }

        if ($salePriceFromFilter = $this->tableFilters['sale_price_from']['sale_price_from'] ?? null) {
            $query->whereHas('saleAdvertisement', function($q) use ($salePriceFromFilter) {
                $q->where('sale_price', '>=', $salePriceFromFilter);
            });
        }

        if ($salePriceToFilter = $this->tableFilters['sale_price_to']['sale_price_to'] ?? null) {
            $query->whereHas('saleAdvertisement', function($q) use ($salePriceToFilter) {
                $q->where('sale_price', '<=', $salePriceToFilter);
            });
        }

        return $query;
    }

    protected function getViewData(): array
    {
        return $this->getStatData();
    }

    abstract protected function getStatData(): array;
}
