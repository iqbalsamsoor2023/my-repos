<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Enums\Residence\EntryLaneType;
use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class LaneTypeChart extends ChartWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected ?string $heading = 'Guard House Lane Type Distribution';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $laneCounts = $shared['lane_types'] ?? [];

        $singleLaneCount = (int) ($laneCounts[EntryLaneType::SINGLE_LANE->value] ?? 0);
        $dualLaneCount = (int) ($laneCounts[EntryLaneType::DUAL_LANE->value] ?? 0);
        $sameLaneCount = (int) ($laneCounts[EntryLaneType::SAME_LANE->value] ?? 0);

        return [
            'datasets' => [
                [
                    'data' => [$singleLaneCount, $dualLaneCount, $sameLaneCount],
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#4BC0C0'],
                ],
            ],
            'labels' => [
                'Single Lane',
                'Dual Lane',
                'Same Lane',
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
