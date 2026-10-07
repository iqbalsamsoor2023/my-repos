<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Models\ResidenceStatsView;
use App\Models\WidgetAggregate;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Support\WidgetColorPalette;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

class ResidentMarketShareChartWidget extends ChartWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 4;

    protected ?string $heading = 'Resident Market Share';

    protected ?string $pollingInterval = null;

    public ?array $subTypes = [];

    public ?array $activationStatusIds = [];

    public ?array $propertyManagementTypes = [];

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    #[On('ddi-resident-market-share-filters-updated')]
    public function applyResidentMarketShareFilters(array $filters): void
    {
        $this->subTypes = DdiWidgetSupport::normalizeIds($filters['sub_types'] ?? []);
        $this->activationStatusIds = DdiWidgetSupport::normalizeIds($filters['activation_status_ids'] ?? []);
        $this->propertyManagementTypes = DdiWidgetSupport::normalizeIds($filters['property_management_types'] ?? []);

        $this->dispatch('$refresh');
    }

    protected function getData(): array
    {
        $totals = $this->getTotals();
        $potential = max($totals['total_units'] - $totals['total_residents'], 0);
        $shareColors = WidgetColorPalette::ddiResidentShareColors();

        return [
            'labels' => ['Residents', 'Potential'],
            'datasets' => [
                [
                    'label' => 'Share',
                    'data' => [(int) $totals['total_residents'], (int) $potential],
                    'backgroundColor' => [$shareColors['residents'], $shareColors['potential']],
                    'borderWidth' => 1,
                    'borderColor' => WidgetColorPalette::whiteHex(),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getTotals(): array
    {
        if (empty($this->provinceIds) && empty($this->districtIds)) {
            $cached = WidgetAggregate::getCached('ddi_global_stats', 300);
            if ($cached && isset($cached['total_units'], $cached['total_residents'])) {
                return [
                    'total_units' => (int) $cached['total_units'],
                    'total_residents' => (int) $cached['total_residents'],
                ];
            }
        }

        $cacheKey = DdiWidgetSupport::cacheKey('resident_market_share_totals', [
            $this->provinceIds,
            $this->districtIds,
            $this->subTypes,
            $this->activationStatusIds,
            $this->propertyManagementTypes,
        ]);

        return Cache::remember($cacheKey, 60, fn () => ResidenceStatsView::aggregateTotals(
            $this->provinceIds,
            $this->districtIds,
            $this->subTypes,
            $this->activationStatusIds,
            $this->propertyManagementTypes,
        ));
    }
}
