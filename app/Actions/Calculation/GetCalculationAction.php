<?php

namespace App\Actions\Calculation;

use App\Enums\Vehicle\VehicleType as VehicleVehicleType;
use App\Enums\Visitor\VehicleType;
use App\Models\Calculation;
use Illuminate\Http\Request;

class GetCalculationAction
{
    public function execute(Request $request)
    {
        $parkingId = $request->parking_id;
        $vehicleType = $request->vehicle_type;
        $isStamp = $request->is_stamp;

        // Start the query
        $query = Calculation::query();

        // Add conditions only when values are present
        if (! is_null($parkingId)) {
            $query->where('parking_id', $parkingId);
        }

        if (! is_null($vehicleType)) {
            if ($vehicleType == VehicleType::MOTORBIKE->value) {
                $vehicleType = VehicleVehicleType::MOTORCYCLE->value;
            } else {
                $vehicleType = VehicleType::CAR;
            }
            $query->where('vehicle_type', $vehicleType);
        }

        if (! is_null($isStamp)) {
            $query->where('is_stamp', $isStamp);
        }

        $calculation = $query->first();

        return $calculation;
    }
}
