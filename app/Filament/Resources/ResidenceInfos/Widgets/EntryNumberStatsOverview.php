<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class EntryNumberStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $entryCounts = (array) ($shared['entry_numbers'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoEntryNumberColors();
        $seriesColors = WidgetColorPalette::residenceInfoEntryNumberSeriesColors();

        $total = array_sum($entryCounts);

        $orderedKeys = collect(array_keys($entryCounts))
            ->filter(static fn (string $key): bool => $key !== 'null')
            ->map(static fn (string $key): int => (int) $key)
            ->sort()
            ->map(static fn (int $entryNumber): string => (string) $entryNumber)
            ->values()
            ->all();

        $orderedKeys[] = 'null';

        return collect($orderedKeys)->values()->map(function (string $entryKey, int $index) use ($entryCounts, $total, $colorMap, $seriesColors): Stat {
            $entryNumber = $entryKey === 'null' ? null : (int) $entryKey;

            $label = $entryNumber !== null
                ? __('residence.entry_number_label', ['count' => $entryNumber])
                : __('user.not_yet_set');

            $fallbackColor = $seriesColors[$index % count($seriesColors)] ?? $colorMap['default'];
            $color = $entryNumber !== null
                ? ($colorMap[$entryNumber] ?? $fallbackColor)
                : $colorMap['null'];

            $count = (int) ($entryCounts[$entryKey] ?? 0);
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            return Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$label}</span>"),
                $count
            )
                ->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                </div>
            "))
                ->description(new HtmlString(
                    '<span>
                <strong class="text-black dark:text-white">'.$percentage.'%</strong> of '.$total.'
                </span>'
                ));
        })->toArray();
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
