<?php

namespace App\Filament\Resources\Vehicles\Widgets\Car;

use App\Enums\Vehicle\FuelType;
use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CarFuelTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

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
        $fuelTypes = $data['car_fuel_types'];
        $total = $data['total_cars'];

        $colorMap = WidgetColorPalette::carFuelTypeColors();

        // Build items with counts, then sort by count descending
        $items = [];
        foreach (FuelType::cases() as $fuelType) {
            $items[] = [
                'label' => $fuelType->label(),
                'count' => $fuelTypes[$fuelType->value] ?? 0,
                'color' => $colorMap[$fuelType->value],
            ];
        }
        $items[] = [
            'label' => __('user.not_yet_set'),
            'count' => $fuelTypes['null'] ?? 0,
            'color' => $colorMap['null'],
        ];

        usort($items, fn ($a, $b) => $b['count'] <=> $a['count']);

        $stats = [];
        foreach ($items as $item) {
            $count = $item['count'];
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $item['color'];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$item['label']}</span>"),
                $count
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$percentage}%)</span>
                </div>
            "))->description(__('vehicle.widgets.from_cars', ['total' => $total]));
        }

        return $stats;
    }
}
