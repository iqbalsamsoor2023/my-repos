<?php

namespace App\Repositories;

use App\Actions\Report\GetReportAction;
use App\Actions\Report\ReportExportAction;
use Illuminate\Http\Request;

class ReportRepository
{
    public function index(Request $request)
    {
        $getReportAction = new GetReportAction;
        $report = $getReportAction->execute($request);

        return $report;
    }

    public function export(array $request)
    {
        $reportAction = new ReportExportAction;
        $report = $reportAction->execute($request);

        return $report;
    }
}
