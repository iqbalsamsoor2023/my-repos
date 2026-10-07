<?php

namespace App\Actions\Vehicle;

use App\Exceptions\GeneralException;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DeleteVehicleAction
{
    public function execute(int $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        if ($vehicle->delete()) {
            // $this->deleteVehicleImage($vehicle);
            // $this->deleteRoadTaxImage($vehicle);
            return 'Success';
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed deleting Vehicle');
    }

    private function deleteVehicleImage(Vehicle $vehicle)
    {
        Storage::disk('cosv5')->delete(config('app.path.cos')."/vehicle/$vehicle->image_name");
    }

    private function deleteRoadTaxImage(Vehicle $vehicle)
    {
        Storage::disk('cosv5')->delete(config('app.path.cos')."/vehicle/$vehicle->image_name");
    }
}
