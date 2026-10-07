<?php

namespace App\Filament\Pages\DataVisualization;

use App\Filament\Resources\PublicMaintenances\Widgets\PublicMaintenancesChart;
use Filament\Pages\Page;

class PublicMaintenance extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.data-visualization.public-maintenance';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.data_visualization');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.public_maintenances');
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return ! auth()->user()->hasRole('Super Admin');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            PublicMaintenancesChart::class,
        ];
    }
}
