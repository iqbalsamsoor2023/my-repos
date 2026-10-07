<?php

namespace App\Actions\VisitorParking;

use App\Models\VisitorLog;
use Carbon\Carbon;

class GetVisitorDurationAction
{
    public function execute(VisitorLog $visitorLog, $calculation)
    {
        $visitorArrived = new Carbon($visitorLog->arrival_time);

        if (is_null($visitorLog->leave_time) == true) {
            $visitorDepart = Carbon::now();
        } else {
            $visitorDepart = new Carbon($visitorLog->leave_time);
        }

        $visitorDuration = $visitorDepart->diffInMinutes($visitorArrived, true);
        $carbon = new Carbon($calculation['free_parking_minutes']);
        $minutes = $carbon->hour;
        $freeParkingInMinutes = ($minutes * 60) + $carbon->minute;

        $visitorDuration = $visitorDuration - $freeParkingInMinutes;

        return $visitorDuration;
    }
}
