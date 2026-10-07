<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class MoobanActivationStatusOverview extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Mooban Activation Status Overview';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $data = $shared['activation_status'] ?? [];

        // Match colors exactly with stats overview
        $colors = [];
        foreach (array_keys($data) as $status) {
            $colors[] = $this->getColorForStatus($status);
        }

        return [
            'labels' => array_keys($data),
            'datasets' => [
                [
                    'label' => 'Mooban Activation Status',
                    'data' => array_values($data),
                    'backgroundColor' => $colors,
                    'borderColor' => $colors, // ensures borders match fill
                    'borderWidth' => 1,       // optional, makes the chart cleaner
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    /**
     * Return the correct color for a given status.
     * Must match the StatsOverview widget for consistency.
     */
    protected function getColorForStatus(string $status): string
    {
        return match ($status) {
            'Inactive - Demo'               => '#9CA3AF', // Gray
            'Inactive - Cancelled Service'  => '#EF4444', // Red
            'Active - GT Only'              => '#EC4899', // Pink
            'Active - GP Only'              => '#3B82F6', // Blue ✅
            'Active - All Action'           => '#10B981', // Green
            'Active - For Demo Only'        => '#F97316', // Orange
            default                         => '#6B7280', // Fallback gray
        };
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}