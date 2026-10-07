<?php

namespace App\Filament\Resources\Vehicles\Widgets\Motorcycle;

use App\Policies\VehiclePolicy;
use App\Services\VehicleWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class MotorcycleTopInsuranceChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return VehiclePolicy::hasAdminDashboardAccess(Auth::user());
    }

    public function getHeading(): ?string
    {
        return __('vehicle.widgets.top_motorcycle_insurance_companies_heading');
    }

    protected function getData(): array
    {
        $data = VehicleWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $insurance = collect($data['motorcycle_insurance']);
        $lineColor = WidgetColorPalette::vehicleCreationTrendColors()['motorcycle'];

        return [
            'labels' => $insurance->map(fn ($item) => $item['name_en'] ?? $item['name'])->toArray(),
            'datasets' => [
                [
                    'label' => __('vehicle.widgets.number_of_motorcycles'),
                    'data' => $insurance->pluck('count')->toArray(),
                    'borderColor' => $lineColor,
                    'backgroundColor' => WidgetColorPalette::vehicleLineFill($lineColor, 0.4),
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
