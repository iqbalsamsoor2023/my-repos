<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\DB;

class VehicleInsuranceCompaniesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('vehicle_insurance_company')
                ->get();

            foreach ($mmb1Data as $key => $value) {
                Company::create([
                    'type' => 'Vehicle Insurance',
                    'name' => $value->name,
                    'name_th' => $value->name_th,
                    'contact_number' => $value->emergency_contact,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at,
                ]);
            }
            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
