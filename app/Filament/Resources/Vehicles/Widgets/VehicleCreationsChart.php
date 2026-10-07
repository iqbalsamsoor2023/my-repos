<?php

namespace App\Filament\Resources\Vehicles\Widgets;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class VehicleCreationsChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    public static function canView(): bool
    {
        return VehiclePolicy::hasAdminDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.vehicle_creations_trend_heading');
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => __('vehicle.widgets.filter_daily'),
            'week' => __('vehicle.widgets.filter_last_7_days'),
            'month' => __('vehicle.widgets.filter_monthly'),
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? 'day';
        $trend = VehicleWidgetDataService::getScopedCreationTrendForUser(Auth::user(), $this->tableFilters ?? [], $filter);
        $colors = WidgetColorPalette::vehicleCreationTrendColors();

        $allDates = array_unique(array_merge(
            array_keys($trend['car']),
            array_keys($trend['motorcycle'])
        ));
        sort($allDates);

        $dateFormat = match ($filter) {
            'month' => 'M Y',
            default => 'M d',
        };

        $labels = array_map(
            fn ($d) => Carbon::parse($d)->format($dateFormat),
            $allDates
        );

        return [
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.cars'),
                    'data' => array_map(fn ($d) => $trend['car'][$d] ?? 0, $allDates),
                    'backgroundColor' => $colors['car'],
                    'borderColor' => $colors['car'],
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => __('vehicle.widgets.motorcycles'),
                    'data' => array_map(fn ($d) => $trend['motorcycle'][$d] ?? 0, $allDates),
                    'backgroundColor' => $colors['motorcycle'],
                    'borderColor' => $colors['motorcycle'],
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
