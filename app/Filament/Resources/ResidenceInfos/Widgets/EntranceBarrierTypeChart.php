<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Enums\Residence\EntranceBarrierType;
use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class EntranceBarrierTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.entrance_barrier_types');
    }

    protected function getData(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = (array) ($shared['entrance_barrier_types'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoEntranceBarrierTypeColors();

        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach (EntranceBarrierType::cases() as $case) {
            $labels[] = $case->label();
            $data[] = (int) ($counts[(string) $case->value] ?? 0);
            $backgroundColors[] = $colorMap[$case->value] ?? '#6B7280';
        }

        // Add Not Yet Set
        $labels[] = __('user.not_yet_set');
        $data[] = (int) ($counts['null'] ?? 0);
        $backgroundColors[] = $colorMap['null'];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('residence.barrier_types'),
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                    'hoverOffset' => 4,
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
