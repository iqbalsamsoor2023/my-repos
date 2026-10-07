<?php

namespace App\Services;

use App\Actions\Company\CreateCompanyAction;
use App\Actions\Vehicle\CreateVehicleAction;
use App\Actions\Vehicle\DeleteVehicleAction;
use App\Actions\Vehicle\UpdateVehicleAction;
use App\Actions\VehicleModel\CreateVehicleModelAction;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleService
{
    public function index(Request $request)
    {
        return Vehicle::with('vehicleModel', 'vehicleModel.vehicleBrand', 'insuranceCompany', 'province')->filter($request->all())->latest('id')->paginate(20);
    }

    public function create(StoreVehicleRequest $request)
    {
        // registered new insurance company for others insurance option
        if (! empty($request->insurance_others)) {
            $companyRequest = new Request;
            $companyRequest->merge([
                'name' => $request->insurance_others,
                'type' => 'Vehicle Insurance',
            ]);
            $companyAction = new CreateCompanyAction;
            $company = $companyAction->execute($companyRequest);
            $request->merge([
                'insurance_company_id' => $company->id,
            ]);
        }

        // registered new vehicle model for others model option
        if (! empty($request->model_others)) {
            $vehicleModelRequest = new Request;
            $vehicleModelRequest->merge([
                'name' => $request->model_others,
                'brand_id' => $request->brand_id,
                'type' => $request->vehicle_type,
            ]);

            $vehicleModelAction = new CreateVehicleModelAction;
            $company = $vehicleModelAction->execute($vehicleModelRequest);
            $request->merge([
                'vehicle_model_id' => $company->id,
            ]);
        }

        $vehicleAction = new CreateVehicleAction;

        return $vehicleAction->execute($request);
    }

    public function show(int $id)
    {
        return Vehicle::with('vehicleModel', 'vehicleModel.vehicleBrand', 'insuranceCompany', 'province')->findOrFail($id);
    }

    public function update(UpdateVehicleRequest $request, int $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicleAction = new UpdateVehicleAction;

        return $vehicleAction->execute($request, $vehicle);
    }

    public function delete(int $id)
    {
        $vehicleAction = new DeleteVehicleAction;

        return $vehicleAction->execute($id);
    }
}
