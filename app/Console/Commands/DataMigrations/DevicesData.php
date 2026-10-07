<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Device;
use Illuminate\Support\Facades\DB;

class DevicesData
{
    public static function execute()
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('devices')
            ->orderBy('devices.id')
            ->chunk(1000, function ($datas) {
                $mmb2Data = [];

                foreach ($datas as $value) {
                    $mmb2Data[] = [
                        'id' => $value->id,
                        'user_id' => $value->user_id,
                        'package_name' => $value->package_name,
                        'brand' => $value->brand ?? null,
                        'model' => $value->model ?? null,
                        'device_token' => $value->reg_token,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at,
                    ];
                }

                Device::insert($mmb2Data);
            });

        return true;
    }
}
