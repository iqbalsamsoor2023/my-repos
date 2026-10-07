<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UnitCreationChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Unit Creation Trends';

    protected int|string|array $columnSpan = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitPolicy::isGlobalAdmin(Auth::user());
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Daily',
            'week' => 'Last 7 Days',
            'month' => 'Monthly',
        ];
    }

    protected function getData(): array
    {
        $period = $this->filter ?? 'day';
        $tableFilters = $this->tableFilters ?? [];

        $trendData = UnitWidgetDataService::getCreationTrend($tableFilters, $period);
        $trend = collect($trendData);

        return [
            'datasets' => [
                [
                    'label' => 'Units Created',
                    'data' => $trend->values()->toArray(),
                    'backgroundColor' => WidgetColorPalette::blueHex(),
                    'borderColor' => WidgetColorPalette::blueHex(),
                ],
            ],
            'labels' => $trend->keys()->map(fn (string $date) => match ($period) {
                'month' => Carbon::parse($date)->format('M Y'),
                default => Carbon::parse($date)->format('M d'),
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
