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

class CarAgeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return VehiclePolicy::hasAdminDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $counts = $data['car_age_buckets'];
        $total = $data['total_cars'];
        $colorMap = WidgetColorPalette::vehicleAge();

        $sorted = $counts;
        arsort($sorted);

        $stats = [];
        foreach ($sorted as $label => $count) {
            $pct = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorMap[$label];
            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$label}</span>"),
                $count
            )->description(__('vehicle.widgets.percent_of_cars', ['percent' => $pct, 'total' => $total]));
        }

        return $stats;
    }
}
