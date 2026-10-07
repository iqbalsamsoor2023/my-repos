<?php

namespace App\Filament\Resources\Parcels\Widgets;

use App\Policies\ParcelPolicy;
use App\Services\ParcelWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class CourierBrandsChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('parcel.courier_brand_distribution');
    }

    public static function canView(): bool
    {
        return ParcelPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getData(): array
    {
        $data = ParcelWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $courierCounts = collect($data['courier_counts']);
        $colors = WidgetColorPalette::parcelCourierSeriesColors();
        $count = $courierCounts->count();

        return [
            'labels' => $courierCounts->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => __('parcel.parcels_by_courier'),
                    'data' => $courierCounts->pluck('count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $count),
                    'borderColor' => array_slice($colors, 0, $count),
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
