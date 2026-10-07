<?php

namespace App\Filament\Resources\Vehicles\Widgets\Car;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class CarBrandsChart extends ChartWidget
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
        return __('vehicle.widgets.top_car_brands_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $brands = collect($data['car_brands']);
        $lineColor = WidgetColorPalette::vehicleCreationTrendColors()['car'];

        return [
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.number_of_cars'),
                    'data' => $brands->pluck('count')->toArray(),
                    'borderColor' => $lineColor,
                    'backgroundColor' => WidgetColorPalette::vehicleLineFill($lineColor, 0.5),
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $brands->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
