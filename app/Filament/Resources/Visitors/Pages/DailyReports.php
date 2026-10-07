<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\VisitorResource;
use App\Filament\Resources\Visitors\Widgets\VisitorDailyReportWidget;
use Filament\Resources\Pages\Page;

class DailyReports extends Page
{
    protected static string $resource = VisitorResource::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.security_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('menu.securityManagement.manage_visitors');
    }

    protected string $view = 'filament.resources.visitors.pages.daily-reports';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VisitorDailyReportWidget::class,
        ];
    }
}
