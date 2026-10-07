<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Enums\Unit\StatusType;
use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class UnitStatusStatsOverview extends BaseWidget
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
        return UnitPolicy::isPropertyManagerOrCenter(Auth::user());
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $filters = $this->tableFilters ?? [];

        $data = UnitWidgetDataService::getDashboardDataForUser($user, $filters);

        if ($data === null) {
            return [];
        }

        $total = (int) $data['total_units'];
        $statusCounts = $data['status_counts'];
        $colorMap = WidgetColorPalette::unitStatusColors();

        $stats = [];

        foreach (StatusType::cases() as $statusType) {
            $count = (int) ($statusCounts[$statusType->value] ?? 0);
            $percentage = formatPercentageForStatOverview($total > 0 ? ($count / $total) * 100 : 0);
            $color = $colorMap[$statusType->value] ?? '#6B7280';

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$statusType->getLabel()}</span>"),
                $count
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$percentage}%)</span>
                </div>
            "))->description("from {$total} units");
        }

        $nullCount = (int) ($statusCounts['null'] ?? 0);
        if ($nullCount > 0 || $total > 0) {
            $percentage = formatPercentageForStatOverview($total > 0 ? ($nullCount / $total) * 100 : 0);
            $color = $colorMap['null'];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">".__('user.not_yet_set').'</span>'),
                $nullCount
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$nullCount}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$percentage}%)</span>
                </div>
            "))->description("from {$total} units");
        }

        return $stats;
    }
}
