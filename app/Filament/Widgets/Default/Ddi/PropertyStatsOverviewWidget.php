<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Residence\SubType;
use App\Models\ResidenceStatsView;
use App\Models\WidgetAggregate;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class PropertyStatsOverviewWidget extends BaseWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    protected function getStats(): array
    {
        $totals = $this->getSubTypeCounts();
        $total = collect($totals)->sum();

        return collect(SubType::publicOptions())
            ->map(function ($label, $value) use ($totals, $total) {
                $count = $totals[$value] ?? 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;

                $subType = SubType::from($value);

                return Stat::make($label, number_format($count))
                    ->description(number_format($total).' '.__('app.total')." ({$percentage}%)")
                    ->descriptionIcon($subType->getIcon())
                    ->color($subType->getColor());
            })
            ->values()
            ->toArray();
    }

    protected function getColumns(): int
    {
        return 4;
    }

    private function getSubTypeCounts(): array
    {
        if (empty($this->provinceIds) && empty($this->districtIds)) {
            $cached = WidgetAggregate::getCached('ddi_global_stats', 300);
            if ($cached && isset($cached['sub_type_counts'])) {
                return $cached['sub_type_counts'];
            }
        }

        $cacheKey = DdiWidgetSupport::cacheKey('stats_overview', [$this->provinceIds, $this->districtIds]);

        return Cache::remember($cacheKey, 60, function () {
            return ResidenceStatsView::query()
                ->publicMoobans()
                ->inProvinces($this->provinceIds)
                ->inDistricts($this->districtIds)
                ->selectRaw('sub_type, COUNT(*) as cnt')
                ->groupBy('sub_type')
                ->pluck('cnt', 'sub_type')
                ->toArray();
        });
    }
}
