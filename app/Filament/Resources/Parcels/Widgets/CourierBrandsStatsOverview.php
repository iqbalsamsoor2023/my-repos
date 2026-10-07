<?php

namespace App\Filament\Resources\Parcels\Widgets;

use App\Policies\ParcelPolicy;
use App\Services\ParcelWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CourierBrandsStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return ParcelPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $data = ParcelWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $total = $data['total_parcels'];
        $colorList = WidgetColorPalette::parcelCourierSeriesColors();
        $stats = [];

        foreach ($data['courier_counts'] as $index => $courier) {
            $count = $courier['count'];
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorList[$index % count($colorList)];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$courier['name']}</span>"),
                $count
            )
                ->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$percentage}%)</span>
                </div>
            "))
                ->description("from {$total} Parcels");
        }

        return $stats;
    }
}
