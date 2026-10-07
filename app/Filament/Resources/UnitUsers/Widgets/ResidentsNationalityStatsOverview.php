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

class ResidentsNationalityStatsOverview extends BaseWidget
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
        $user = Auth::user();
        $colorList = WidgetColorPalette::unitUserCountrySeriesColors();
        $notSetColor = WidgetColorPalette::unitUserNotSetHex();
        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? []);
        $total = (int) ($data['total_residents'] ?? 0);

        return collect($data['country_counts'] ?? [])
            ->map(function ($count, $countryName) {
                return (object) ['country_name' => $countryName, 'total' => $count];
            })
            ->values()
            ->map(function ($row, $index) use ($total, $colorList, $notSetColor) {
                $isNotSet = $row->country_name === 'Not Yet Set';
                $percentage = $total > 0 ? round(($row->total / $total) * 100, 2) : 0;
                $color = $isNotSet ? $notSetColor : $colorList[$index % count($colorList)];
                $countryName = $isNotSet ? __('user.not_yet_set') : $row->country_name;

                return Stat::make(
                    new HtmlString("<span style=\"color:{$color}; font-weight:bold;\">{$countryName}</span>"),
                    new HtmlString(
                        "
                        <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                            <span style=\"color:{$color}; font-weight:bold;\">{$row->total}</span>
                            <span style=\"font-size:0.75em;\">({$percentage}%)</span>
                        </div>
                    "
                    )
                )->description("of {$total} resident records");
            })
            ->toArray();
    }
}
