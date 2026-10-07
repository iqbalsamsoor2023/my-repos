<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use Filament\Widgets\Widget;

class IrsDailyReportWidget extends Widget
{
    protected string $view = 'filament.resources.incident-reports.widgets.irs-daily-report-widget';

    protected int|string|array $columnSpan = 'full';
}
