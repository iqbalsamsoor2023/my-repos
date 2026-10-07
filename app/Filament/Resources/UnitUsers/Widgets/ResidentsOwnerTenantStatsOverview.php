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

class ResidentsOwnerTenantStatsOverview extends BaseWidget
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
        return UnitUserPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $data = UnitUserWidgetDataService::getScopedDataForUser($user, $this->tableFilters ?? []);
        $colors = WidgetColorPalette::unitUserOwnerTenantColors();
        $ownerCount = (int) ($data['ownership_counts']['owner'] ?? 0);
        $tenantCount = (int) ($data['ownership_counts']['tenant'] ?? 0);
        $total = (int) ($data['total_residents'] ?? 0);

        $ownerPercentage = $total > 0 ? round(($ownerCount / $total) * 100, 2) : 0;
        $tenantPercentage = $total > 0 ? round(($tenantCount / $total) * 100, 2) : 0;

        return [
            Stat::make(
                new HtmlString('<span style="color:'.$colors['owner'].'; font-weight:bold;">Owner</span>'),
                new HtmlString(
                    "
                    <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                        <span style=\"color:{$colors['owner']}; font-weight:bold; font-size:1.75rem;\">{$ownerCount}</span>
                        <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$ownerPercentage}%)</span>
                    </div>
                "
                )
            )->description("from {$total} resident records"),
            Stat::make(
                new HtmlString('<span style="color:'.$colors['tenant'].'; font-weight:bold;">Tenant</span>'),
                new HtmlString(
                    "
                    <div style=\"display:flex; justify-content:space-between; align-items:center;\">
                        <span style=\"color:{$colors['tenant']}; font-weight:bold; font-size:1.75rem;\">{$tenantCount}</span>
                        <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$tenantPercentage}%)</span>
                    </div>
                "
                )
            )->description("from {$total} resident records"),
        ];
    }
}
