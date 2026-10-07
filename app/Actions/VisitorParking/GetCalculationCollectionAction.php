<?php

namespace App\Actions\VisitorParking;

use App\Models\Calculation;
use App\Models\Parking;
use Illuminate\Support\Collection;

class GetCalculationCollectionAction
{
    public function execute($visitorParking, Parking $parking, $is_stamp, $vehicle_type): Collection
    {
        if ($visitorParking) {
            $calculation = $visitorParking->calculation_records;
        } else {
            $calculation = Calculation::where('parking_id', '=', $parking->id)
                ->where('vehicle_type', '=', $vehicle_type)
                ->where('is_stamp', '=', $is_stamp)
                ->first();

            $calculation = collect($calculation);
        }

        return $calculation;
    }
}
