<?php

namespace App\Actions\VisitorParking;

use Carbon\Carbon;

class GetCharteredDurationAction
{
    public function execute($visitorDuration, $calculation)
    {
        $carbon = new Carbon($calculation['chartered_duration']);
        $minutes = $carbon->hour;
        $charteredDuration = ($minutes * 60) + $carbon->minute;

        if ($visitorDuration >= $charteredDuration) {
            $visitorDuration = $visitorDuration - $charteredDuration;
        }

        return $visitorDuration;
    }
}
