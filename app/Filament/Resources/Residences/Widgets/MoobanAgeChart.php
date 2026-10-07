<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class MoobanAgeChart extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected ?string $heading = 'Residence Age Distribution';

    protected int|string|array $columnSpan = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = $shared['age_buckets'] ?? [];

        $labels = array_keys($counts);
        $data = array_values($counts);
        $backgroundColors = $this->generateColors(count($labels));

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Number of Residences',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }

    /**
     * Generate visually distinct colors for each age bucket
     */
    protected function generateColors(int $count): array
    {
        $palette = [
            '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107',
            '#FF9800', '#FF5722', '#F44336', '#E91E63', '#9C27B0',
            '#673AB7', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4',
        ];

        return array_slice(
            array_pad($palette, $count, '#95a5a6'),
            0,
            $count
        );
    }
}