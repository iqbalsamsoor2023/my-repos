<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Company;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use Exception;
use Illuminate\Support\Facades\DB;

class VehiclesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1_user_vehicles = DB::connection('mmb1')
                ->table('user_vehicles')
                ->select('*', 'user_vehicles.id as id', 'user_vehicles.created_at as created_at', 'user_vehicles.updated_at as updated_at', 'user_vehicles.deleted_at as deleted_at')
                ->leftJoin('residence_units', 'residence_units.id', 'user_vehicles.residence_unit_id')
                ->where('residence_units.residence_id', $residence_id)
                ->get();

            foreach ($mmb1_user_vehicles as $mmb1_user_vehicle) {
                Vehicle::withoutEvents(function () use ($mmb1_user_vehicle) {

                    // unit_id
                    if (isset($mmb1_user_vehicle->residence_unit_id)) {
                        $mmb1_unit = DB::connection('mmb1')
                            ->table('residence_units')
                            ->where('id', $mmb1_user_vehicle->residence_unit_id)
                            ->first();

                        $mmb2_unit = Unit::withTrashed()->where('home_id', $mmb1_unit->home_id)
                            ->where('unit_number', $mmb1_unit->unit)
                            ->first();
                    }

                    // user
                    if (isset($mmb1_user_vehicle->residence_user_id)) {
                        $mmb1_user = DB::connection('mmb1')
                            ->table('users')
                            ->leftJoin('residence_users', 'residence_users.user_id', 'users.id')
                            ->where('residence_users.id', $mmb1_user_vehicle->residence_user_id)
                            ->first();
                        $user = User::withTrashed()->where('email', $mmb1_user->email)->first();
                    }

                    // insurance vehicle is not from insurance vehicle company
                    $mmb2_vehicle_insurance = null;
                    if (isset($mmb1_user_vehicle->insurance_company)) {
                        $mmb1_vehicle_insurance = DB::connection('mmb1')
                            ->table('vehicle_insurance_company')
                            ->where('id', $mmb1_user_vehicle->insurance_company)
                            ->first();
                        $mmb2_vehicle_insurance = Company::where('name', $mmb1_vehicle_insurance->name ?? null)->where('type', 'Vehicle Insurance')->first();
                    }

                    $created_vehicle = Vehicle::create([
                        'unit_id' => $mmb2_unit->id ?? null,
                        'user_id' => $user->id ?? null,
                        'province_id' => $mmb1_user_vehicle->province_id,
                        'vehicle_model_id' => $mmb1_user_vehicle->model_id == 0 ? null : $mmb1_user_vehicle->model_id,
                        'insurance_company_id' => $mmb2_vehicle_insurance->id ?? null,
                        'insurance_company_id' => $mmb2_vehicle_insurance->id ?? null,
                        'plate_number' => $mmb1_user_vehicle->plate_no,
                        'generated_vehicle_no' => $mmb1_user_vehicle->vehicle_id,
                        'purchase_year' => $mmb1_user_vehicle->year_bought,
                        'roadtax_expiry_date' => $mmb1_user_vehicle->road_tax_expiry_date == '0000-00-00' ? null : $mmb1_user_vehicle->road_tax_expiry_date,
                        'insurance_expiry_date' => $mmb1_user_vehicle->insurance_expiry_date == '0000-00-00' ? null : $mmb1_user_vehicle->insurance_expiry_date,
                        'policy_no' => $mmb1_user_vehicle->policy_no,
                        'is_access_card' => $mmb1_user_vehicle->access_card,
                        'is_car_sticker' => $mmb1_user_vehicle->car_sticker,
                        'created_at' => $mmb1_user_vehicle->created_at,
                        'updated_at' => $mmb1_user_vehicle->updated_at,
                        'deleted_at' => $mmb1_user_vehicle->deleted_at,
                    ]);
                });
            }
            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
