<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\OtherAmenity;
use Exception;
use Illuminate\Support\Facades\DB;

class OtherAmenitiesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('warranties')
                ->where('residence_id', $residence_id)
                ->get();

            foreach ($mmb1Data as $key => $value) {
                $other_amenity = OtherAmenity::create([
                    'residence_id' => $value->residence_id,
                    'is_other_amenity' => isset($value->remark_for_other) ? 1 : 0,
                    'remark' => $value->remark_for_other,
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
