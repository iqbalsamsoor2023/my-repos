<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

class VehicleModelsData
{
    /**
     * migrate all developer coz one developer has many
     */
    public static function execute($residence_id)
    {
        DB::beginTransaction();
        $mmb1Data = DB::connection('mmb1')
            ->table('vehicle_models')
            ->get();

        foreach ($mmb1Data as $key => $value) {
            VehicleModel::create([
                'id' => $value->id,
                'brand_id' => $value->brand_id,
                'name' => $value->name,
                'type' => $value->type,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
            ]);
        }

        DB::commit();
    }
}
