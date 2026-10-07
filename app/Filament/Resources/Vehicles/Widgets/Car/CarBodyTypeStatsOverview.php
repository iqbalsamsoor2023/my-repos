<?php

namespace App\Filament\Resources\Vehicles\Widgets\Car;

use App\Enums\Vehicle\CarBodyType;
use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CarBodyTypeStatsOverview extends BaseWidget
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
        $bodyTypes = $data['car_body_types'];
        $total = $data['total_cars'];

        $colorMap = WidgetColorPalette::carBodyTypeColors();

        $items = [];
        foreach (CarBodyType::cases() as $case) {
            $items[] = [
                'label' => $case->label(),
                'count' => $bodyTypes[$case->value] ?? 0,
                'color' => $colorMap[$case->value],
            ];
        }
        $items[] = [
            'label' => __('user.not_yet_set'),
            'count' => $bodyTypes['null'] ?? 0,
            'color' => $colorMap['null'],
        ];

        usort($items, fn ($a, $b) => $b['count'] <=> $a['count']);

        $stats = [];
        foreach ($items as $item) {
            $count = $item['count'];
            $pct = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$item['color']};font-weight:bold;\">{$item['label']}</span>"),
                $count
            )->description(__('vehicle.widgets.percent_of_cars', ['percent' => $pct, 'total' => $total]));
        }

        return $stats;
    }
}
