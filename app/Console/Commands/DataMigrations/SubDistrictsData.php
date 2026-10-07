<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Erp\ThailandSubDistrict;
use Exception;
use Illuminate\Support\Facades\DB;

class SubDistrictsData
{
    public static function execute()
    {
        try {
            $mmb1Data = DB::connection('mmb1')
                ->table('subdistricts')
                ->get();

            $mmb2Data = [];

            foreach ($mmb1Data as $key => $value) {
                $mmb2Data[] = [
                    'id' => $value->id,
                    'code' => $value->code,
                    'name_in_thai' => $value->name_th,
                    'name_in_english' => $value->name,
                    'latitude' => $value->latitude,
                    'longitude' => $value->longitude,
                    'district_id' => $value->district_id,
                    'zip_code' => $value->zip_code,
                ];
            }

            foreach (array_chunk($mmb2Data, 1000) as $chunk) {
                ThailandSubDistrict::insert($chunk);
            }

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
