<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ResidentCreationChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Resident Creation Trends';

    protected int|string|array $columnSpan = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Last 30 Days',
            'week' => 'Last 7 Days',
            'month' => 'Last 6 Months',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? 'day';
        $dateFormat = $filter === 'month' ? 'M Y' : 'M d';
        $user = Auth::user();
        $trendColor = WidgetColorPalette::unitUserCreationTrendHex();

        $trend = UnitUserWidgetDataService::getScopedCreationTrendForUser(
            $user,
            $this->tableFilters ?? [],
            $filter,
        );

        return [
            'datasets' => [
                [
                    'label' => 'Residents Created',
                    'data' => array_values($trend),
                    'backgroundColor' => $trendColor,
                    'borderColor' => $trendColor,
                    'tension' => 0.4,
                    'fill' => false,
                ],
            ],
            'labels' => collect(array_keys($trend))
                ->map(fn (string $date) => Carbon::parse($date)->format($dateFormat))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
