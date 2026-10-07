<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Enums\Residence\EntryLaneType;
use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class GuardHouseLaneTypeStatsOverview extends BaseWidget
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
        $laneTypeCounts = (array) ($shared['guard_house_lane_types'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoGuardHouseLaneTypeColors();

        $total = array_sum($laneTypeCounts);

        $stats = [];

        // Loop through all enum values + null
        $types = collect(EntryLaneType::cases())->pluck('value')->toArray();
        $types[] = null;

        foreach ($types as $type) {
            $key = $type === null ? 'null' : (string) $type;
            $count = (int) ($laneTypeCounts[$key] ?? 0);
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            $label = $type !== null && ($enum = EntryLaneType::tryFrom($type))
                ? $enum->getLabel()
                : __('user.not_yet_set');

            $color = $type === null ? $colorMap['null'] : ($colorMap[$type] ?? WidgetColorPalette::neutralHex());

            $stats[] = Stat::make(
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

        return $stats;
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
