<?php

namespace App\Filament\Resources\CheckPointLogs\Pages;

use App\Filament\Resources\CheckPointLogs\CheckPointLogResource;
use App\Filament\Resources\CheckPointLogs\Widgets\CheckpointLogDailyReportWidget;
use Filament\Resources\Pages\Page;

class DailyReports extends Page
{
    protected static string $resource = CheckPointLogResource::class;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.resources.checkpoint-logs.pages.daily-reports';

    public static function getNavigationGroup(): ?string
    {
        return __('menu.security_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('menu.securityManagement.manage_patrol_checkpoint_pgs');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_pgs_daily_reports');
    }

    public function getTitle(): string
    {
        return __('menu.securityManagement.manage_pgs_daily_reports');
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CheckpointLogDailyReportWidget::class,
        ];
    }
}
