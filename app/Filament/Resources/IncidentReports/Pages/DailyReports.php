<?php

namespace App\Filament\Resources\IncidentReports\Pages;

use App\Filament\Resources\IncidentReports\IncidentReportResource;
use App\Filament\Resources\IncidentReports\Widgets\IrsDailyReportWidget;
use Filament\Resources\Pages\Page;

class DailyReports extends Page
{
    protected static string $resource = IncidentReportResource::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.security_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('menu.securityManagement.manage_incident_reports_irs');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.securityManagement.manage_irs_daily_reports');
    }

    protected string $view = 'filament.resources.incident-reports.pages.daily-reports';

    protected function getHeaderWidgets(): array
    {
        return [
            IrsDailyReportWidget::class,
        ];
    }
}
