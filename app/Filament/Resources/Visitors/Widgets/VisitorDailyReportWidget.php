<?php

namespace App\Filament\Resources\Visitors\Widgets;

use Filament\Widgets\Widget;

class VisitorDailyReportWidget extends Widget
{
    protected string $view = 'filament.resources.visitors.widgets.visitor-daily-report-widget';

    protected int|string|array $columnSpan = 'full';
}
