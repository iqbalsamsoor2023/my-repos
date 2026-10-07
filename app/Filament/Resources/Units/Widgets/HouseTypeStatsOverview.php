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

class HouseTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return UnitPolicy::isGlobalAdmin(Auth::user());
    }

    protected function getCards(): array
    {
        $filters = $this->tableFilters ?? [];
        $data = UnitWidgetDataService::getAllData($filters);
        $countsByHouseTypeAndSubType = $data['house_type_sub_type'];
        $notSetCount = $data['house_type_null_count'];
        $total = (int) $data['total_units'];

        $cards = collect(HouseType::cases())
            ->map(function (HouseType $houseType) use ($countsByHouseTypeAndSubType, $total) {
                $subType = $houseType->subType();
                $count = (int) ($countsByHouseTypeAndSubType[$houseType->value][$subType->value] ?? 0);
                $percentage = $total > 0 ? ($count / $total * 100) : 0;
                $percentageFormatted = formatPercentageForStatOverview($percentage);
                $color = WidgetColorPalette::unitHouseTypeHex($houseType->value);

                return Stat::make(
                    new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$houseType->label()}</span>"),
                    new HtmlString("
                        <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                            <span style=\"color:{$color};font-weight:bold;\">{$count}</span>
                            <span style=\"text-align: right;font-size: 0.75em;\">({$percentageFormatted}%)</span>
                        </div>
                    ")
                )->description("of {$total} total");
            })
            ->values();

        $notSetColor = WidgetColorPalette::unitHouseTypeHex('null');
        $notSetPct = $total > 0 ? formatPercentageForStatOverview($notSetCount / $total * 100) : 0;
        $cards->push(
            Stat::make(
                new HtmlString('<span style="color:'.$notSetColor.';font-weight:bold;">Not Set</span>'),
                new HtmlString("
                    <div style=\"display: flex; justify-content: space-between; align-items: center;\">
                        <span style=\"color:{$notSetColor};font-weight:bold;\">{$notSetCount}</span>
                        <span style=\"text-align: right;font-size: 0.75em;\">({$notSetPct}%)</span>
                    </div>
                ")
            )->description("of {$total} total")
        );

        return $cards->toArray();
    }
}
