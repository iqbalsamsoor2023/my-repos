<?php

namespace App\Filament\Resources\CheckPointLogs\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\CheckPointLogs\CheckPointLogResource;
use App\Filament\Resources\CheckPointLogs\Widgets\RoundKpiChart;
use App\Filament\Resources\CheckPointLogs\Widgets\RoundProgressWidget;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListCheckPointLogs extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = CheckPointLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            RoundProgressWidget::class,
            RoundKpiChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make(__('app.download_daily_report'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('filament.admin.resources.check-point-logs.daily-reports'))
                ->hidden(function () {
                    return ! auth()->user()->hasRole(['Property Management', 'Super Admin', 'Admin']);
                }),
            Action::make('index')
                ->label(__('menu.patrol_guard_checkpoints'))
                ->url(route('filament.admin.resources.rounds.index')),
        ];
    }
}
