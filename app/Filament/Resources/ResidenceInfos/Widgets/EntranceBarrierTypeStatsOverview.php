<?php

namespace App\Filament\Resources\ResidenceInfos\Widgets;

use App\Enums\Residence\EntranceBarrierType;
use App\Services\ResidenceInfoWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class EntranceBarrierTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $shared = ResidenceInfoWidgetDataService::getAllData($this->tableFilters ?? []);
        $counts = (array) ($shared['entrance_barrier_types'] ?? []);
        $colorMap = WidgetColorPalette::residenceInfoEntranceBarrierTypeColors();
        $total = (int) ($shared['total_residences'] ?? 0);

        $stats = [];

        // Build stats dynamically
        $types = [
            EntranceBarrierType::GATE_FENCE->value,
            EntranceBarrierType::LPR->value,
            EntranceBarrierType::RFID->value,
            EntranceBarrierType::MANUAL_BARRIER->value,
            EntranceBarrierType::NO_BARRIER->value,
            null,
        ];

        foreach ($types as $type) {
            $key = $type === null ? 'null' : (string) $type;
            $count = (int) ($counts[$key] ?? 0);
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            $label = is_int($type)
                ? EntranceBarrierType::tryFrom($type)?->label() ?? 'Unknown'
                : __('user.not_yet_set');

            $color = $type === null
                ? $colorMap['null']
                : ($colorMap[$type] ?? WidgetColorPalette::neutralHex());

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$label}</span>"),
                $count
            )
                ->value(new HtmlString("
                    <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                        <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                    </div>
                "))
                ->description(new HtmlString("<span><strong class=\"text-black dark:text-white\">{$percentage}%</strong> of {$total}</span>"));
        }

        return $stats;
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
