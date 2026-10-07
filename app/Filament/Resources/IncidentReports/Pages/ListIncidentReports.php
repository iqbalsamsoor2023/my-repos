<?php

namespace App\Filament\Resources\IncidentReports\Pages;

use App\Filament\Resources\IncidentReports\Widgets\IrsChartOverview;
use App\Filament\Resources\IncidentReports\Widgets\IrsStatsOverview;
use App\Filament\Resources\IncidentReports\Widgets\IrsReportDownloadWidget;
use App\Filament\Resources\IncidentReports\IncidentReportResource;
use App\Filament\Resources\IncidentReports\Widgets\IrsByCategoryDoughnutChart;
use App\Filament\Resources\IncidentReports\Widgets\IrsByTitleDoughnutChart;
use App\Filament\Resources\IncidentReports\Widgets\IrsTrendChart;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListIncidentReports extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = IncidentReportResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            // IrsChartOverview::class,
            // IrsStatsOverview::class,
            IrsByCategoryDoughnutChart::class,
            IrsByTitleDoughnutChart::class,
            IrsTrendChart::class,
            IrsReportDownloadWidget::class,
        ];
    }
}

