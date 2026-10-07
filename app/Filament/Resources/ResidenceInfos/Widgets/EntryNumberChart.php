<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class EntryNumberChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.guard_house_entry_distribution');
    }

    protected function getData(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $entryCounts = (array) ($shared['entry_numbers'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoEntryNumberColors();
        $seriesColors = WidgetColorPalette::residenceInfoEntryNumberSeriesColors();

        $labels = [];
        $data = [];
        $colors = [];

        $entryNumbers = collect(array_keys($entryCounts))
            ->filter(static fn (string $key): bool => $key !== 'null')
            ->map(static fn (string $key): int => (int) $key)
            ->sort()
            ->values();

        foreach ($entryNumbers as $index => $entryNumber) {
            $labels[] = __('residence.entry_number_label', ['count' => $entryNumber]);
            $data[] = (int) ($entryCounts[(string) $entryNumber] ?? 0);

            $fallbackColor = $seriesColors[$index % count($seriesColors)] ?? $colorMap['default'];
            $colors[] = $colorMap[$entryNumber] ?? $fallbackColor;
        }

        $labels[] = __('user.not_yet_set');
        $data[] = (int) ($entryCounts['null'] ?? 0);
        $colors[] = $colorMap['null'];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('residence.guard_house_entries'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
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
