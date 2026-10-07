<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class GuardHouseRoofStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 2;
    }

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = (array) ($shared['guard_house_roof'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoGuardHouseRoofColors();

        $total = array_sum($counts);

        return [
            $this->makeStat(__('residence.has_roof_value'), $counts['has_roof'], $total, $colorMap['has_roof']),
            $this->makeStat(__('residence.no_roof'), $counts['no_roof'], $total, $colorMap['no_roof']),
            $this->makeStat(__('user.not_yet_set'), $counts['null'], $total, $colorMap['null']),
        ];
    }

    private function makeStat(string $label, int $count, int $total, string $color): Stat
    {
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
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
