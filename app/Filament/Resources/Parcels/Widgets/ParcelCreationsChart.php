<?php

namespace App\Filament\Resources\Parcels\Widgets;

use App\Policies\ParcelPolicy;
use App\Services\ParcelWidgetDataService;
use App\Support\WidgetColorPalette;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class ParcelCreationsChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('parcel.parcel_summary_trend');
    }

    public static function canView(): bool
    {
        return ParcelPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => __('app.daily'),
            'week' => 'Last 7 Days',
            'month' => __('app.monthly'),
        ];
    }

    protected function getData(): array
    {
        $period = $this->filter ?? 'day';
        $trend = ParcelWidgetDataService::getScopedCreationTrendForUser(Auth::user(), $this->tableFilters ?? [], $period);

        $dateFormat = match ($period) {
            'month' => 'M Y',
            default => 'M d',
        };

        $labels = array_map(fn ($d) => Carbon::parse($d)->format($dateFormat), array_keys($trend));
        $hex = WidgetColorPalette::parcelCreationTrendHex();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('parcel.parcel'),
                    'data' => array_values($trend),
                    'backgroundColor' => $hex,
                    'borderColor' => $hex,
                    'fill' => false,
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
