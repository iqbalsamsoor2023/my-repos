<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Erp\ThailandDistrict;
use Illuminate\Support\Facades\DB;

class DistrictsData
{
    public static function execute()
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('districts')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = [
                'id' => $value->id,
                'code' => $value->code,
                'name_in_thai' => $value->name_th,
                'name_in_english' => $value->name,
                'province_id' => $value->province_id,
            ];
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            ThailandDistrict::insert($chunk);
        }

        return true;
    }
}
