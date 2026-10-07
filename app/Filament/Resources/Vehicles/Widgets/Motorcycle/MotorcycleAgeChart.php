<?php

namespace App\Filament\Resources\Vehicles\Widgets\Motorcycle;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class MotorcycleAgeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    public static function canView(): bool
    {
        return VehiclePolicy::hasAdminDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.motorcycle_age_distribution_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $counts = $data['motorcycle_age_buckets'];

        return [
            'labels' => array_keys($counts),
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.motorcycles_by_age'),
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
