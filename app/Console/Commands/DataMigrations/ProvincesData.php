<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Erp\ThailandProvince;
use Illuminate\Support\Facades\DB;

class ProvincesData
{
    public static function execute()
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('provinces')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = [
                'id' => $value->id,
                'code' => $value->code,
                'name_in_thai' => $value->name_th,
                'name_in_english' => $value->name,
            ];
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            ThailandProvince::insert($chunk);
        }

        return true;
    }
}
