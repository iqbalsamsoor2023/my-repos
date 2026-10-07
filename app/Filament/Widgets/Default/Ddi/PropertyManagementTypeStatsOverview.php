<?php

namespace App\Filament\Widgets\Default\Ddi;

use App\Enums\Residence\PropertyManagementType;
use App\Models\ResidenceStatsView;
use App\Policies\DistrictDashboardPolicy;
use App\Support\DdiWidgetSupport;
use App\Traits\DdiFiltersProvinceAndDistrict;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class PropertyManagementTypeStatsOverview extends BaseWidget
{
    use DdiFiltersProvinceAndDistrict;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 8;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $cacheKey = DdiWidgetSupport::cacheKey('property_management_stats', [$this->provinceIds, $this->districtIds]);

        $counts = Cache::remember($cacheKey, 60, function () {
            return ResidenceStatsView::query()
                ->publicMoobans()
                ->inProvinces($this->provinceIds)
                ->inDistricts($this->districtIds)
                ->selectRaw('property_management_type, COUNT(*) as total')
                ->groupBy('property_management_type')
                ->pluck('total', 'property_management_type')
                ->toArray();
        });

        $grandTotal = (int) array_sum($counts);

        return collect(PropertyManagementType::cases())->map(function (PropertyManagementType $type) use ($counts, $grandTotal) {
            $count = (int) ($counts[$type->value] ?? 0);
            $color = $type->getHex();
            $label = $type->getShortLabel() === $type->getLabel()
                ? $type->getLabel()
                : "{$type->getLabel()} ({$type->getShortLabel()})";
            $percentage = $grandTotal > 0 ? round(($count / $grandTotal) * 100, 2) : 0;

            return Stat::make(
                new HtmlString("<span class=\"font-semibold\" style=\"color:{$color};\">{$label}</span>"),
                number_format($count)
            )
                ->value(new HtmlString("\n                    <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">\n                        <span style=\"color:{$color}; font-weight:bold; font-size:1.5rem;\">".number_format($count)."</span>\n                        <span style=\"font-size:1rem;\">({$percentage}%)</span>\n                    </div>\n                "))
                ->description('from '.number_format($grandTotal).' total residences')
                ->color($type->getColor());
        })->values()->toArray();
    }
}
