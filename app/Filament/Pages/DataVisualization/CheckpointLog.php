<?php

namespace App\Filament\Pages\DataVisualization;

use App\Filament\Resources\CheckPointLogs\Widgets\CheckpointLogChartOverviewWidget;
use App\Filament\Resources\CheckPointLogs\Widgets\CheckpointLogStatsOverviewWidget;
use Filament\Pages\Page;

class CheckpointLog extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.data-visualization.checkpoint-log';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.data_visualization');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.checkpoint_logs');
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return ! auth()->user()->hasRole('Super Admin');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            CheckpointLogChartOverviewWidget::class,
            CheckpointLogStatsOverviewWidget::class,
        ];
    }
}
