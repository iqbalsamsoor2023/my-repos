<?php

namespace App\Filament\Pages\DataVisualization;

use App\Filament\Resources\Parcels\Widgets\PmsLineChart;
use App\Filament\Resources\Parcels\Widgets\PmsStatsOverview;
use Filament\Pages\Page;

class Parcel extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.data-visualization.parcel';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.data_visualization');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.parcels');
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return ! auth()->user()->hasRole('Super Admin');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            PmsLineChart::class,
            PmsStatsOverview::class,
        ];
    }
}
