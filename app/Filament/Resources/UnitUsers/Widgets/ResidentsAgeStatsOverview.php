<?php

namespace App\Filament\Resources\UnitUsers\Widgets;

use App\Policies\UnitUserPolicy;
use App\Services\UnitUserWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ResidentsAgeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $orderedGroups = ['06-12', '13-22', '23-30', '31-40', '41-50', '51-60', '61-70', '>70', 'Not Set'];
        $colorMap = WidgetColorPalette::unitUserAgeGroupColors();
        $user = Auth::user();

        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? []);
        $results = collect($data['age_group_counts'] ?? []);
        $total = (int) ($data['total_residents'] ?? 0);

        $stats = [];

        foreach ($orderedGroups as $group) {
            $count = (int) ($results[$group] ?? 0);
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorMap[$group];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color}; font-weight:bold;\">{$group}</span>"),
                new HtmlString(
                    "
                    <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                        <span style=\"color:{$color}; font-weight:bold;\">{$count}</span>
                        <span style=\"font-size:0.75em;\">({$percentage}%)</span>
                    </div>
                "
                )
            )->description("of {$total} resident records");
        }

        return $stats;
    }
}
