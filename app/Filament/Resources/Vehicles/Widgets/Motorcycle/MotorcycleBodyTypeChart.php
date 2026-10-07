<?php

namespace App\Filament\Resources\Vehicles\Widgets\Motorcycle;

use App\Enums\Vehicle\MotorcycleBodyType;
use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class MotorcycleBodyTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    public static function canView(): bool
    {
        return VehiclePolicy::hasAdminDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.motorcycle_body_type_distribution_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $bodyTypes = $data['motorcycle_body_types'];

        $colorMap = WidgetColorPalette::motorcycleBodyTypeColors();

        $labels = array_merge(
            array_map(fn ($c) => $c->label(), MotorcycleBodyType::cases()),
            [__('user.not_yet_set')]
        );

        $values = array_merge(
            array_map(fn ($c) => $bodyTypes[$c->value] ?? 0, MotorcycleBodyType::cases()),
            [$bodyTypes['null'] ?? 0]
        );

        $colors = array_merge(
            array_map(fn ($c) => $colorMap[$c->value], MotorcycleBodyType::cases()),
            [$colorMap['null']]
        );

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.motorcycle_body_type'),
                    'data' => $values,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
