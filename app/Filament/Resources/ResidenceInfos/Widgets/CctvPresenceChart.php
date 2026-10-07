<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class CctvPresenceChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.cctv_presence');
    }

    protected function getData(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = (array) ($shared['cctv_presence'] ?? []);
        $colors = WidgetColorPalette::residenceInfoCctvPresenceColors();

        $yesCount = (int) ($counts['yes'] ?? 0);
        $noCount = (int) ($counts['no'] ?? 0);
        $notSetCount = (int) ($counts['not_set'] ?? 0);

        return [
            'labels' => [__('app.yes'), __('app.no'), __('user.not_yet_set')],
            'datasets' => [
                [
                    'label' => __('residence.cctv_presence'),
                    'data' => [$yesCount, $noCount, $notSetCount],
                    'backgroundColor' => [$colors['yes'], $colors['no'], $colors['not_set']],
                    'borderColor' => [$colors['yes'], $colors['no'], $colors['not_set']],
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
