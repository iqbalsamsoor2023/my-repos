<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Enums\Residence\SubType;
use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SubTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitPolicy::isGlobalAdmin(Auth::user());
    }

    protected function getCards(): array
    {
        $filters = $this->tableFilters ?? [];
        $data = UnitWidgetDataService::getAllData($filters);
        $total = $data['total_units'];
        $subTypeCounts = $data['sub_type_counts'];

        $visibleSubTypes = [
            SubType::POOL_VILLA,
            SubType::SINGLE_HOME,
            SubType::TWIN_HOME,
            SubType::TOWN_HOME,
            SubType::HOME_OFFICE,
            SubType::CONDO_HIGH_RISE,
            SubType::CONDO_LOW_RISE,
        ];

        $cards = collect($visibleSubTypes)->map(function (SubType $subType) use ($subTypeCounts, $total) {
            $count = (int) ($subTypeCounts[$subType->value] ?? 0);
            $percentageFormatted = formatPercentageForStatOverview($total > 0 ? ($count / $total * 100) : 0);
            $color = WidgetColorPalette::ddiSubTypeHex($subType->value);

            return Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$subType->getLabel()}</span>"),
                $count
            )
                ->value(new HtmlString("
                    <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                        <span style=\"color:{$color};font-weight:bold;\">{$count}</span>
                        <span style=\"text-align: right;font-size: 0.75em;\">({$percentageFormatted}%)</span>
                    </div>
                "))
                ->description("from {$total} total");
        });

        $countNotSet = (int) ($subTypeCounts['null'] ?? 0);
        if ($countNotSet > 0) {
            $percentageFormatted = formatPercentageForStatOverview($total > 0 ? ($countNotSet / $total * 100) : 0);
            $color = WidgetColorPalette::neutralHex();

            $cards->push(
                Stat::make(
                    new HtmlString("<span style=\"color:{$color};font-weight:bold;\">Not Set</span>"),
                    $countNotSet
                )
                    ->value(new HtmlString("
                        <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                            <span style=\"color:{$color};font-weight:bold;\">{$countNotSet}</span>
                            <span style=\"text-align: right;font-size: 0.75em;\">({$percentageFormatted}%)</span>
                        </div>
                    "))
                    ->description("from {$total} total")
            );
        }

        return $cards->values()->toArray();
    }
}
