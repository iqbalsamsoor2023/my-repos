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

class MotorcycleInsuranceCompanyStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

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
        $insurance = $data['motorcycle_insurance'];
        $total = $data['total_motorcycles'];

        $colorList = WidgetColorPalette::vehicleInsuranceSeriesColors();

        $stats = [];

        foreach ($insurance as $index => $company) {
            $count = $company['count'];
            $pct = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorList[$index % count($colorList)];
            $displayName = $company['name_en'] ?? $company['name'];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$displayName}</span>"),
                $count
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$pct}%)</span>
                </div>
            "))->description(__('vehicle.widgets.from_motorcycles', ['total' => $total]));
        }

        return $stats;
    }
}
