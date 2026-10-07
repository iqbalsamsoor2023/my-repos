<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class EntryQuantityChart extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Guard House Entry Number Distribution';

    protected function getData(): array
    {
        $entryNumbers = [1, 2, 3, 4, 5];

        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = $shared['entry_numbers'] ?? [];

        $counts[null] = $counts[null] ?? 0;

        $labels = [];
        $data = [];
        $colors = [
            '#FF6384',
            '#36A2EB',
            '#FFCE56',
            '#4BC0C0',
            '#9966FF',
            '#FF9F40',
        ];

        foreach ($entryNumbers as $entry) {
            $labels[] = "Entry $entry";
            $data[] = $counts[$entry] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
