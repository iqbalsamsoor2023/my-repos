<?php

namespace App\Filament\Resources\Vehicles\Widgets\Car;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class CarAgeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    public static function canView(): bool
    {
        return VehiclePolicy::hasAdminDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.car_age_distribution_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $counts = $data['car_age_buckets'];

        return [
            'labels' => array_keys($counts),
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.cars_by_age'),
                    'data' => array_values($counts),
                    'backgroundColor' => array_values(WidgetColorPalette::vehicleAge()),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
