<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use App\Services\UnitWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SignUpRateStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 2;
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

        $colors = WidgetColorPalette::unitSignUpColors();

        $total = (int) $data['total_units'];
        $signed = (int) $data['signed_up_count'];
        $notSigned = max($total - $signed, 0);

        $signedPct = formatPercentageForStatOverview($total > 0 ? ($signed / $total) * 100 : 0);
        $notSignedPct = formatPercentageForStatOverview($total > 0 ? ($notSigned / $total) * 100 : 0);

        return [
            Stat::make(
                new HtmlString('<span style="color:'.$colors['signed_up'].';font-weight:bold;">Signed Up</span>'),
                $signed
            )->value(new HtmlString('
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                    <span style="color:'.$colors['signed_up']."; font-weight:bold; font-size:1.75rem;\">{$signed}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$signedPct}%)</span>
                </div>
            "))->description("from {$total} units"),

            Stat::make(
                new HtmlString('<span style="color:'.$colors['not_signed_up'].';font-weight:bold;">Not Yet Signed Up</span>'),
                $notSigned
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$colors['not_signed_up']}; font-weight:bold; font-size:1.75rem;\">{$notSigned}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$notSignedPct}%)</span>
                </div>
            "))->description("from {$total} units"),
        ];
    }
}
