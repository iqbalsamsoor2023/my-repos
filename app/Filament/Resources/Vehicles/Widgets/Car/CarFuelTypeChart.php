<?php

namespace App\Filament\Resources\Vehicles\Widgets\Car;

use App\Enums\Vehicle\FuelType;
use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class CarFuelTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return VehiclePolicy::hasDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.car_fuel_type_distribution_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $fuelTypes = $data['car_fuel_types'];

        $colorMap = WidgetColorPalette::carFuelTypeColors();

        $labels = array_merge(
            array_map(fn ($case) => $case->label(), FuelType::cases()),
            [__('user.not_yet_set')]
        );

        $values = array_merge(
            array_map(fn ($case) => $fuelTypes[$case->value] ?? 0, FuelType::cases()),
            [$fuelTypes['null'] ?? 0]
        );

        $colors = array_merge(
            array_map(fn ($case) => $colorMap[$case->value], FuelType::cases()),
            [$colorMap['null']]
        );

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.fuel_type'),
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
