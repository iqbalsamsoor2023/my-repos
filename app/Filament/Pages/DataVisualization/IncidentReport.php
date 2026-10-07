<?php

namespace App\Filament\Pages\DataVisualization;

use App\Filament\Resources\IncidentReports\Widgets\IrsChartOverview;
use App\Filament\Resources\IncidentReports\Widgets\IrsStatsOverview;
use Filament\Pages\Page;

class IncidentReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.data-visualization.incident-report';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.data_visualization');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.incident_reports');
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return ! auth()->user()->hasRole('Super Admin');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            IrsChartOverview::class,
            IrsStatsOverview::class,
        ];
    }
}
