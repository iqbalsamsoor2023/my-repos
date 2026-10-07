<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class GuardHouseRoofChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.guard_house_type');
    }

    protected function getData(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = (array) ($shared['guard_house_roof'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoGuardHouseRoofColors();

        return [
            'labels' => [__('residence.has_roof_value'), __('residence.no_roof'), __('user.not_yet_set')],
            'datasets' => [
                [
                    'label' => __('residence.roof_status'),
                    'data' => [$counts['has_roof'], $counts['no_roof'], $counts['null']],
                    'backgroundColor' => [$colorMap['has_roof'], $colorMap['no_roof'], $colorMap['null']],
                    'borderColor' => [$colorMap['has_roof'], $colorMap['no_roof'], $colorMap['null']],
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
