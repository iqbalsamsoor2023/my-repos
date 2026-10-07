<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Models\ResidenceStatsView;
use App\Models\WidgetAggregate;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Support\WidgetColorPalette;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class ResidentPotentialStatsOverviewWidget extends BaseWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 8;

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

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $totals = $this->getTotals();

        $totalUnits = (int) $totals['total_units'];
        $totalResidents = (int) $totals['total_residents'];
        $potential = max($totalUnits - $totalResidents, 0);
        $potentialPercentage = $totalUnits > 0 ? round(($potential / $totalUnits) * 100, 1) : 0;
        $shareColors = WidgetColorPalette::ddiResidentShareColors();
        $unitsColor = WidgetColorPalette::emeraldHex();
        $residentsColor = $shareColors['residents'];
        $potentialColor = $shareColors['potential'];

        return [
            Stat::make(
                new HtmlString("<span class=\"font-semibold\" style=\"color:{$unitsColor};\">Total Units</span>"),
                new HtmlString("<div class=\"flex items-baseline gap-2 flex-wrap\"><span class=\"font-bold text-xl\" style=\"color:{$unitsColor};\">".number_format($totalUnits).'</span></div>')
            )
                ->description('Filtered by selected province/district')
                ->color(WidgetColorPalette::statColorFromHex($unitsColor)),

            Stat::make(
                new HtmlString("<span class=\"font-semibold\" style=\"color:{$residentsColor};\">Total Residents</span>"),
                new HtmlString("<div class=\"flex items-baseline gap-2 flex-wrap\"><span class=\"font-bold text-xl\" style=\"color:{$residentsColor};\">".number_format($totalResidents).'</span></div>')
            )
                ->description('Current resident footprint')
                ->color(WidgetColorPalette::statColorFromHex($residentsColor)),

            Stat::make(
                new HtmlString("<span class=\"font-semibold\" style=\"color:{$potentialColor};\">Potentials</span>"),
                new HtmlString("<div class=\"flex items-baseline gap-2 flex-wrap\"><span class=\"font-bold text-xl\" style=\"color:{$potentialColor};\">".number_format($potential).' ('.$potentialPercentage.'%)</span></div>')
            )
                ->description('Potential = Total Units - Total Residents')
                ->color(WidgetColorPalette::statColorFromHex($potentialColor)),
        ];
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

        $cacheKey = DdiWidgetSupport::cacheKey('potential_stats_totals', [
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
