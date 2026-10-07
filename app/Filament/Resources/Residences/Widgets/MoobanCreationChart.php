<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Carbon;

class MoobanCreationChart extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected ?string $heading = 'Mooban Creation Trends';

    protected int|string|array $columnSpan = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

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
        $filter = $this->filter ?? 'day';

        // Determine period start and interval
        [$periodStart, $interval] = match ($filter) {
            'week' => [Carbon::now()->subDays(6)->startOfDay(), 'day'], // last 7 days
            'month' => [Carbon::now()->subMonths(5)->startOfMonth(), 'month'], // last 6 months
            default => [Carbon::now()->subDays(29)->startOfDay(), 'day'], // last 30 days
        };

        // Fetch shared trend data once
        $trendMap = ResidenceWidgetDataService::getAllData($this->tableFilters ?? [])['creation_trend'] ?? [];

        $labels = [];
        $data = [];

        $current = $periodStart->copy();

        while ($current->lte(Carbon::now())) {
            if ($interval === 'month') {
                $label = $current->format('M Y');
                // Sum all counts in this month
                $monthlyCount = 0;
                foreach ($trendMap as $date => $count) {
                    if (Carbon::parse($date)->format('M Y') === $label) {
                        $monthlyCount += (int) $count;
                    }
                }
                $labels[] = $label;
                $data[] = $monthlyCount;
                $current->addMonth();
            } else {
                $label = $current->format('M d');
                $key = $current->format('Y-m-d');
                $labels[] = $label;
                $data[] = (int) ($trendMap[$key] ?? 0);
                $current->addDay();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Residences Created',
                    'data' => $data,
                    'backgroundColor' => '#4f46e5',
                    'borderColor' => '#4f46e5',
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}