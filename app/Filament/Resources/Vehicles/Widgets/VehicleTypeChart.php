<?php

namespace App\Filament\Resources\Vehicles\Widgets;

use App\Enums\Vehicle\VehicleType;
use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class VehicleTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return VehiclePolicy::hasDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.vehicle_type_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $colors = WidgetColorPalette::vehicleTypeColors();

        return [
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.vehicle_distribution'),
                    'data' => [$data['total_cars'], $data['total_motorcycles']],
                    'backgroundColor' => [$colors['car'], $colors['motorcycle']],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => [
                VehicleType::CAR->category(),
                VehicleType::MOTORCYCLE->category(),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
