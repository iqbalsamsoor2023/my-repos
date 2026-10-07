<?php

namespace App\Filament\Resources\Pets\Widgets;

use App\Policies\PetPolicy;
use App\Services\PetWidgetDataService;
use App\Support\WidgetColorPalette;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class PetsCreationChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    public function getHeading(): string|Htmlable|null
    {
        return __('pet.widgets.creation_chart_heading');
    }

    public static function canView(): bool
    {
        return PetPolicy::hasAdminDashboardAccess(Auth::user());
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => __('pet.widgets.daily'),
            'week' => __('pet.widgets.last_7_days'),
            'month' => __('pet.widgets.monthly'),
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? 'day';

        $trend = PetWidgetDataService::getScopedCreationTrendForUser(
            Auth::user(),
            $this->tableFilters ?? [],
            $filter
        );

        $dateFormat = match ($filter) {
            'day' => 'M d',
            'week' => 'D',
            default => 'M Y',
        };

        $dogTrend = $trend['dog'] ?? [];
        $catTrend = $trend['cat'] ?? [];
        $allDates = array_values(array_unique(array_merge(array_keys($dogTrend), array_keys($catTrend))));
        sort($allDates);

        $labels = array_map(fn (string $date): string => Carbon::parse($date)->format($dateFormat), $allDates);
        $dogSeries = array_map(fn (string $date): int => (int) ($dogTrend[$date] ?? 0), $allDates);
        $catSeries = array_map(fn (string $date): int => (int) ($catTrend[$date] ?? 0), $allDates);

        $trendColors = WidgetColorPalette::petCreationTrendColors();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('pet.dog'),
                    'data' => $dogSeries,
                    'borderColor' => $trendColors['dog'],
                    'backgroundColor' => $trendColors['dog'],
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => __('pet.cat'),
                    'data' => $catSeries,
                    'borderColor' => $trendColors['cat'],
                    'backgroundColor' => $trendColors['cat'],
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
