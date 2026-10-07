<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Enums\Residence\SubType;
use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class PublicTypeChart extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected ?string $heading = 'Public Type';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    /**
     * Returns the color palette for subtypes.
     * Must match PublicTypeStatsOverview colors.
     */
    protected function getSubTypeColors(): array
    {
        return [
            SubType::POOL_VILLA->value        => '#EF4444', // Red
            SubType::SINGLE_HOME->value       => '#2563EB', // Dark Blue
            SubType::TWIN_HOME->value         => '#F59E0B', // Amber/Orange
            SubType::TOWN_HOME->value         => '#16A34A', // Dark Green
            SubType::HOME_OFFICE->value       => '#7C3AED', // Purple
            SubType::CONDO_HIGH_RISE->value   => '#DB2777', // Magenta
            SubType::CONDO_LOW_RISE->value    => '#0EA5E9', // Sky Blue
        ];
    }

    protected function getData(): array
    {
        // Use the same subtypes as stats overview
        $subTypes = [
            SubType::POOL_VILLA,
            SubType::SINGLE_HOME,
            SubType::TWIN_HOME,
            SubType::TOWN_HOME,
            SubType::HOME_OFFICE,
            SubType::CONDO_HIGH_RISE,
            SubType::CONDO_LOW_RISE,
        ];

        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $totals = $shared['sub_types'] ?? [];

        $labels = [];
        $data = [];
        $colors = $this->getSubTypeColors();

        foreach ($subTypes as $subType) {
            $value = $subType->value;
            $labels[] = $subType->getLabel();
            $data[] = $totals[$value] ?? 0;
        }

        $backgroundColors = array_map(fn($subType) => $colors[$subType->value] ?? '#6B7280', $subTypes);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Public Type',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors, // match fill
                    'borderWidth' => 1,
                    'hoverOffset' => 6,
                ],
            ],
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