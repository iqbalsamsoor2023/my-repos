<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class CctvRangeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $rangeCounts = (array) ($shared['cctv_ranges'] ?? []);
        $totalResidences = (int) ($shared['total_residences'] ?? 0);
        $colors = WidgetColorPalette::residenceInfoCctvRangeColors();

        $stats = [];

        // Add CCTV ranges
        foreach (range(1, 10) as $i) {
            $rangeLabel = (($i - 1) * 10 + 1).'-'.($i * 10);
            $siteCount = (int) ($rangeCounts['range_'.$i] ?? 0);
            $percentage = $totalResidences > 0 ? round(($siteCount / $totalResidences) * 100, 2) : 0;
            $color = $colors[$i - 1] ?? '#6B7280';

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">CCTV {$rangeLabel}</span>"),
                "{$siteCount} Sites"
            )
                ->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$siteCount}</span>
                </div>
            "))
                ->description(new HtmlString("<span><strong class=\"text-black dark:text-white\">{$percentage}%</strong> of {$totalResidences}</span>"));
        }

        // Add "Not Yet Set"
        $notYetSetCount = (int) ($rangeCounts['not_set'] ?? 0);
        $percentage = $totalResidences > 0 ? round(($notYetSetCount / $totalResidences) * 100, 2) : 0;

        $stats[] = Stat::make(
            new HtmlString('<span style="color:#6B7280;font-weight:bold;">'.__('user.not_yet_set').'</span>'),
            "{$notYetSetCount} Sites"
        )
            ->value(new HtmlString("
            <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                <span style=\"color:#6B7280; font-weight:bold; font-size:1.75rem;\">{$notYetSetCount}</span>
            </div>
        "))
            ->description(new HtmlString("<span><strong class=\"text-black dark:text-white\">{$percentage}%</strong> of {$totalResidences}</span>"));

        return $stats;
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
