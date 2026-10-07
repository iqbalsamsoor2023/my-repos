<?php

namespace App\Http\Controllers;

use App\Actions\DailyActivityReport\GenerateDailyActivityReportAction;
use App\Models\Sgoc\DailyActivityReport;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyActivityReportController extends Controller
{
    public function report(DailyActivityReport $dailyActivityReport)
    {
        $generateDailyActivityReportAction = new GenerateDailyActivityReportAction();
        $data = $generateDailyActivityReportAction->execute($dailyActivityReport);

        $pdf = PDF::loadView('daily-activity-reports.full-report', $data)->setPaper('A4', 'portrait');

        return $pdf->stream();
    }
}
