<?php

namespace App\Console\Commands\DataMigrations;

use Exception;
use Illuminate\Support\Facades\DB;

class FacilitiesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('facilities')
                ->where('residence_id', $residence_id)
                ->orderBy('facilities.id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        DB::table('facilities')->insert([
                            'residence_id' => $value->residence_id,
                            'name' => $value->name,
                            'booking_per_hour' => $value->limit_per_hour,
                            'price_per_hour' => $value->price_per_hour,
                            'price_per_day' => $value->price_per_day,
                            'is_active' => $value->status,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ]);
                    }
                });
            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
