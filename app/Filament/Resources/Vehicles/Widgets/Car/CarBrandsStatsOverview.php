<?php

namespace App\Filament\Resources\Vehicles\Widgets\Car;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CarBrandsStatsOverview extends BaseWidget
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
        $brands = $data['car_brands'];
        $total = $data['total_cars'];

        $colorList = WidgetColorPalette::vehicleBrandSeriesColors();

        $stats = [];

        foreach ($brands as $index => $brand) {
            $count = $brand['count'];
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorList[$index % count($colorList)];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color}; font-weight:bold;\">{$brand['name']}</span>"),
                new HtmlString("
                    <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                        <span style=\"color:{$color}; font-weight:bold;\">{$count}</span>
                        <span style=\"font-size:0.75em;\">({$percentage}%)</span>
                    </div>
                ")
            )->description(__('vehicle.widgets.from_cars', ['total' => $total]));
        }

        return $stats;
    }
}
