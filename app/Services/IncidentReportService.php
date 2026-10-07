<?php

namespace App\Services;

use App\Actions\IncidentReport\GetIncidentReportAction;

class IncidentReportService
{
    public function index($request)
    {
        $getIncidentReportAction = new GetIncidentReportAction;
        $incidentReport = $getIncidentReportAction->execute($request);

        return $incidentReport;
    }
}
