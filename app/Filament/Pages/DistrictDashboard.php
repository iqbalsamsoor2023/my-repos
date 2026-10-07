<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Default\Ddi\DdiFilterWidget;
use App\Filament\Widgets\Default\Ddi\DistrictDataSummaryWidget;
use App\Filament\Widgets\Default\Ddi\HouseTypeByResidenceStatsOverviewWidget;
use App\Filament\Widgets\Default\Ddi\MarketShareProgressWidget;
use App\Filament\Widgets\Default\Ddi\PropertyManagementTypeChart;
use App\Filament\Widgets\Default\Ddi\PropertyManagementTypeStatsOverview;
use App\Filament\Widgets\Default\Ddi\PropertyStatsOverviewWidget;
use App\Filament\Widgets\Default\Ddi\PropertyStatusGridCardsWidget;
use App\Filament\Widgets\Default\Ddi\ResidentMarketShareChartWidget;
use App\Filament\Widgets\Default\Ddi\ResidentPotentialStatsOverviewWidget;
use App\Policies\DistrictDashboardPolicy;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class DistrictDashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    protected static string $routePath = 'district-dashboard';

    public static function getNavigationGroup(): ?string
    {
        return __('menu.big_data_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.residence_ddi');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    public static function canAccess(): bool
    {
        return Gate::allows(DistrictDashboardPolicy::VIEW_ABILITY);
    }

    /**
     * Get the widgets for the dashboard
     */
    public function getWidgets(): array
    {
        return [
            DdiFilterWidget::class,
            PropertyStatsOverviewWidget::class,
            PropertyStatusGridCardsWidget::class,
            PropertyManagementTypeChart::class,
            PropertyManagementTypeStatsOverview::class,
            HouseTypeByResidenceStatsOverviewWidget::class,
            MarketShareProgressWidget::class,
            ResidentMarketShareChartWidget::class,
            ResidentPotentialStatsOverviewWidget::class,
            DistrictDataSummaryWidget::class,
        ];
    }

    /**
     * Get the number of columns for the dashboard grid
     * Filament uses a 12-column grid system by default
     */
    public function getColumns(): int|array
    {
        return 12;
    }
}
