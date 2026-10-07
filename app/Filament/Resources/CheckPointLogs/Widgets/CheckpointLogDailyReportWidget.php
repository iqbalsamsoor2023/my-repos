<?php

namespace App\Filament\Resources\CheckPointLogs\Widgets;

use Filament\Widgets\Widget;

class CheckpointLogDailyReportWidget extends Widget
{
    protected string $view = 'filament.resources.checkpoint-logs.widgets.checkpoint-daily-report-widget';

    protected int|string|array $columnSpan = 'full';
}

