<?php

namespace App\Actions\DailyActivityReport;

use App\Models\Sgoc\DailyActivityReport;

class GenerateDailyActivityReportAction
{
    public function execute(DailyActivityReport $dailyActivityReport)
    {
        $data = [
            'dailyActivityReport' => $dailyActivityReport,
            'logo' =>  $dailyActivityReport?->reportedBy?->company?->logoUrl,
        ];

        return $data;
    }
}