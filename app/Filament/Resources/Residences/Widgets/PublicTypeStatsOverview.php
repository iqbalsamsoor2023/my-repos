<?php

namespace App\Filament\Resources\Residences\Widgets;

use App\Enums\Residence\SubType;
use App\Services\ResidenceWidgetDataService;
use App\Traits\ResidenceWidgetFilters;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class PublicTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable, ResidenceWidgetFilters;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    // Map subtypes directly to colors
    protected function getSubTypeColors(): array
    {
        return [
            SubType::POOL_VILLA->value        => '#EF4444', // Red
            SubType::SINGLE_HOME->value       => '#2563EB', // Dark Blue
            SubType::TWIN_HOME->value         => '#F59E0B', // Amber/Orange
            SubType::TOWN_HOME->value         => '#16A34A', // Dark Green
            SubType::HOME_OFFICE->value       => '#7C3AED', // Purple
            SubType::CONDO_HIGH_RISE->value   => '#DB2777', // Magenta
            SubType::CONDO_LOW_RISE->value    => '#0EA5E9', // Sky Blue
        ];
    }

    protected function getStats(): array
    {
        $shared = ResidenceWidgetDataService::getAllData($this->tableFilters ?? []);
        $total = $shared['total'] ?? 0;
        $totals = $shared['sub_types'] ?? [];

        $colors = $this->getSubTypeColors();

        $allowedSubTypes = [
            SubType::POOL_VILLA,
            SubType::SINGLE_HOME,
            SubType::TWIN_HOME,
            SubType::TOWN_HOME,
            SubType::HOME_OFFICE,
            SubType::CONDO_HIGH_RISE,
            SubType::CONDO_LOW_RISE,
        ];

        return collect($allowedSubTypes)
            ->map(function (SubType $subType) use ($totals, $total, $colors) {
                $label = $subType->getLabel();
                $value = $subType->value;
                $count = $totals[$value] ?? 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $color = $colors[$value] ?? '#6B7280'; // fallback gray

                return Stat::make(
                    new HtmlString("<span class=\"font-semibold\" style=\"color:{$color};\">{$label}</span>"),
                    $count
                )
                    ->value(new HtmlString("
                    <div class=\"flex items-baseline gap-2 flex-wrap\">
                        <span class=\"font-bold text-xl\" style=\"color:{$color};\">{$count}</span>
                    </div>
                "))
                    ->description(new HtmlString(
                        '<span>
                        <strong class="text-black dark:text-white">' . $percentage . '%</strong> of ' . $total . '
                    </span>'
                    ));
            })
            ->values()
            ->toArray();
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
