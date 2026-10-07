<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Enums\Unit\HouseType;
use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class HouseTypeByResidenceStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return UnitPolicy::isPropertyManager(Auth::user());
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $filters = $this->tableFilters ?? [];

        $data = UnitWidgetDataService::getDashboardDataForUser($user, $filters);

        if ($data === null) {
            return [];
        }

        $houseTypeSubType = $data['house_type_sub_type'];
        $notSetCount = (int) $data['house_type_null_count'];
        $total = (int) $data['total_units'];

        // Derive per-house_type counts from pre-aggregated grid
        $counts = [];
        foreach ($houseTypeSubType as $ht => $stCounts) {
            $counts[(int) $ht] = array_sum($stCounts);
        }

        $stats = [];

        foreach (HouseType::cases() as $houseType) {
            $count = $counts[$houseType->value] ?? 0;
            if ($count === 0) {
                continue;
            }
            $percentage = formatPercentageForStatOverview($total > 0 ? ($count / $total) * 100 : 0);
            $color = WidgetColorPalette::unitHouseTypeHex($houseType->value);

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$houseType->label()}</span>"),
                $count
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.5rem;\">{$count}</span>
                    <span style=\"font-size:1rem;\">({$percentage}%)</span>
                </div>
            "))->description("from {$total} units");
        }

        if ($notSetCount > 0) {
            $percentage = formatPercentageForStatOverview($total > 0 ? ($notSetCount / $total) * 100 : 0);
            $color = WidgetColorPalette::unitHouseTypeHex('null');

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">".__('user.not_yet_set').'</span>'),
                $notSetCount
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.5rem;\">{$notSetCount}</span>
                    <span style=\"font-size:1rem;\">({$percentage}%)</span>
                </div>
            "))->description("from {$total} units");
        }

        return $stats;
    }
}
