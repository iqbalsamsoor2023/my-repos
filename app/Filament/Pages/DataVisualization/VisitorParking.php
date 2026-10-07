<?php

namespace App\Filament\Pages\DataVisualization;

use App\Filament\Resources\VisitorParkings\Widgets\PfmsCollectionsLineChart;
use App\Filament\Resources\VisitorParkings\Widgets\PfmsStatsOverview;
use Filament\Pages\Page;

class VisitorParking extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.data-visualization.visitor-parking';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.data_visualization');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.visitor_parkings');
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return ! auth()->user()->hasRole('Super Admin');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            PfmsCollectionsLineChart::class,
            PfmsStatsOverview::class,
        ];
    }
}
