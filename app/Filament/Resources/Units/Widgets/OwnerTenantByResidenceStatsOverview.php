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

class OwnerTenantByResidenceStatsOverview extends BaseWidget
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

        $total = (int) $data['total_units'];
        $ownerCount = (int) $data['owner_count'];
        $tenantCount = (int) $data['tenant_count'];
        $colors = WidgetColorPalette::unitOwnerTenantColors();

        $ownerPct = formatPercentageForStatOverview($total > 0 ? ($ownerCount / $total) * 100 : 0);
        $tenantPct = formatPercentageForStatOverview($total > 0 ? ($tenantCount / $total) * 100 : 0);

        return [
            Stat::make(
                new HtmlString('<span style="color:'.$colors['owner'].';font-weight:bold;">Occupied by Owner</span>'),
                $ownerCount
            )->value(new HtmlString('
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:'.$colors['owner']."; font-weight:bold; font-size:1.75rem;\">{$ownerCount}</span>
                    <span style=\"font-size:1rem; margin-left:0.5rem;\">({$ownerPct}%)</span>
                </div>
            "))->description("from {$total} total units"),

            Stat::make(
                new HtmlString('<span style="color:'.$colors['tenant'].';font-weight:bold;">Occupied by Tenant</span>'),
                $tenantCount
            )->value(new HtmlString('
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:'.$colors['tenant']."; font-weight:bold; font-size:1.75rem;\">{$tenantCount}</span>
                    <span style=\"font-size:1rem; margin-left:0.5rem;\">({$tenantPct}%)</span>
                </div>
            "))->description("from {$total} total units"),
        ];
    }
}
