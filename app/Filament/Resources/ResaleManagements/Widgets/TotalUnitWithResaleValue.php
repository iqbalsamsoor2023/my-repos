<?php

namespace App\Filament\Resources\ResaleManagements\Widgets;

use App\Enums\ResalesManagement\ResalesManagementStatus;
use App\Enums\SalesAndTenancies\BankLoanStatusEnum;
use App\Enums\User\RoleType;
use App\Models\Unit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TotalUnitWithResaleValue extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.widgets.custom.triple-in-one-stats';

    protected int | string | array $columnSpan = 1;

    protected function getViewData(): array
    {
        // get only RTM module enabled residence units

        $unitQuery = Unit::with(['resaleAdvertisement']);

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

        $totalUnitQuery = $unitQuery->clone();

        // Apply table filters
        $residenceIdFilter = $this->tableFilters['residence_id']['value'] ?? null;
        if ($residenceIdFilter) {
            $unitQuery->where('units.residence_id', $residenceIdFilter);
        }

        $unitIdFilter = $this->tableFilters['unit_id']['value'] ?? null;
        if ($unitIdFilter) {
            $unitQuery->where('units.id', $unitIdFilter);
        }

        $ownershipDocumentFilter = $this->tableFilters['have_ownership_documents']['value'] ?? null;
        if ($ownershipDocumentFilter) {
            $unitQuery->whereHas('resaleAdvertisement', function ($query) use ($ownershipDocumentFilter) {
                $query->where('have_ownership_documents', $ownershipDocumentFilter);
            });
        }

        $bankLoanFilter = $this->tableFilters['bank_loan_status']['value'] ?? null;
        if ($bankLoanFilter) {
            $unitQuery->whereHas('resaleAdvertisement', function ($query) use ($bankLoanFilter) {
                $query->where('bank_loan_status', BankLoanStatusEnum::from($bankLoanFilter)->value);
            });
        }

        $resalePriceFromFilter = $this->tableFilters['resale_price_from']['value'] ?? null;
        if ($resalePriceFromFilter) {
            $unitQuery->whereHas('resaleAdvertisement', function ($query) use ($resalePriceFromFilter) {
                $query->where('resale_price', '>=', $resalePriceFromFilter);
            });
        }

        $resalePriceToFilter = $this->tableFilters['resale_price_to']['value'] ?? null;
        if ($resalePriceToFilter) {
            $unitQuery->whereHas('resaleAdvertisement', function ($query) use ($resalePriceToFilter) {
                $query->where('resale_price', '<=', $resalePriceToFilter);
            });
        }

        $resaleStatusFilter = $this->tableFilters['status']['value'] ?? null;
        if ($resaleStatusFilter) {
            $unitQuery->whereHas('resaleAdvertisement', function ($query) use ($resaleStatusFilter) {
                $query->where('status', ResalesManagementStatus::from($resaleStatusFilter)->value);
            });
        }

        $isActiveFilter = $this->tableFilters['is_active']['value'] ?? null;
        if ($isActiveFilter !== null) {
            $unitQuery->whereHas('resaleAdvertisement', function ($query) use ($isActiveFilter) {
                $query->where('is_active', $isActiveFilter);
            });
        }

        // end applying table filters

        $totalUnits = $totalUnitQuery->count();

        $unitsForResale = $unitQuery->where(function ($subQuery) {
            $subQuery->whereHas('resaleAdvertisement', function ($query) {
                $query->where('is_active', 1)->where('status', ResalesManagementStatus::FOR_RESALE->value);
            });
        })->count();

        $valueOfResaleUnits = $unitQuery->get()->sum(function ($unit) {
            return $unit->resaleAdvertisement ? $unit->resaleAdvertisement->resale_price : 0;
        });

        return [
            'major' => [
                'label' => __('resales-and-tenancies.total_units'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'green',
                'value' => $totalUnits,
            ],
            'minorTop' => [
                'label' => __('resales-and-tenancies.units_for_resale'),
                'icon' => 'heroicon-o-users',
                'color' => '#3B82F6',
                'value' => $unitsForResale,
            ],
            'minorBottom' => [
                'label' => __('resales-and-tenancies.value_of_resale_units'),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'orange',
                'value' => Number::format($valueOfResaleUnits),
            ],
        ];
    }
}
