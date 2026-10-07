<?php

namespace App\Filament\Resources\Vehicles\Widgets\Motorcycle;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class MotorcycleBrandsStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return VehiclePolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $brands = $data['motorcycle_brands'];
        $total = $data['total_motorcycles'];

        $colorList = WidgetColorPalette::vehicleBrandSeriesColors();

        $stats = [];

        foreach ($brands as $index => $brand) {
            $count = $brand['count'];
            $pct = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorList[$index % count($colorList)];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color}; font-weight:bold;\">{$brand['name']}</span>"),
                new HtmlString("
                    <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                        <span style=\"color:{$color}; font-weight:bold;\">{$count}</span>
                        <span style=\"font-size:0.75em;\">({$pct}%)</span>
                    </div>
                ")
            )->description(__('vehicle.widgets.from_motorcycles', ['total' => $total]));
        }

        return $stats;
    }
}
