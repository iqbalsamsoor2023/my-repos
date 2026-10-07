<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Amenity;
use Exception;
use Illuminate\Support\Facades\DB;

class AmenitiesData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $not_empty = DB::connection('mmb1')
                ->table('private_amenities')
                ->where('residence_id', $residence_id)
                ->orderBy('private_amenities.id', 'asc')->first();
            if ($not_empty) {
                $mmb1Data = DB::connection('mmb1')
                    ->table('private_amenities')
                    ->where('residence_id', $residence_id)
                    ->orderBy('private_amenities.id', 'asc')
                    ->chunk(1000, function ($datas) {
                        foreach ($datas as $value) {
                            if ($value->period_type == 'months') {
                                $period_type = 'month';
                            } elseif ($value->period_type == 'years') {
                                $period_type = 'year';
                            }

                            $amenity = Amenity::create([
                                'residence_id' => $value->residence_id,
                                'amenity_name' => $value->amenity_name,
                                'warranty_period' => $value->warranty_period ?? null,
                                'period_type' => $period_type ?? null,
                                'supplier' => $value->supplier,
                                'is_out_warranty' => empty($value->remark) == true ? 0 : 1,
                                'remark' => $value->remark,
                                'created_at' => $value->created_at,
                                'updated_at' => $value->updated_at,
                            ]);
                        }

                        Amenity::create([
                            'residence_id' => $value->residence_id,
                            'amenity_name' => 'Others',
                            'warranty_period' => 0,
                            'period_type' => null,
                            'supplier' => null,
                            'is_out_warranty' => 0,
                            'remark' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
            } else {
                Amenity::create([
                    'residence_id' => $residence_id,
                    'amenity_name' => 'Others',
                    'warranty_period' => 0,
                    'period_type' => null,
                    'supplier' => null,
                    'is_out_warranty' => 0,
                    'remark' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
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
