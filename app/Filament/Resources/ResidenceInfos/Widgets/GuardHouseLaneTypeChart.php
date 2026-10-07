<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Enums\Residence\EntryLaneType;
use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class GuardHouseLaneTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.guard_house_lane_types');
    }

    protected function getData(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $laneTypeCounts = (array) ($shared['guard_house_lane_types'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoGuardHouseLaneTypeColors();

        $labels = [];
        $counts = [];
        $backgroundColors = [];

        // Include all enum values plus null
        $types = collect(EntryLaneType::cases())->pluck('value')->toArray();
        $types[] = null;

        foreach ($types as $type) {
            $label = $type !== null && ($enum = EntryLaneType::tryFrom($type))
                ? $enum->getLabel()
                : __('user.not_yet_set');

            $key = $type === null ? 'null' : (string) $type;
            $count = (int) ($laneTypeCounts[$key] ?? 0);
            $color = $type === null ? $colorMap['null'] : ($colorMap[$type] ?? WidgetColorPalette::neutralHex());

            $labels[] = $label;
            $counts[] = $count;
            $backgroundColors[] = $color;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('residence.lane_types'),
                    'data' => $counts,
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
