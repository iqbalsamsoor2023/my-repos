<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Calculation;
use App\Models\Parking;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class ParkingsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $mmb1_parking_fee_model = DB::connection('mmb1')
                ->table('parking_fee_models')
                ->where('residence_id', $residence_id)
                ->first();

            $discount_type = 0;
            if (isset($mmb1_parking_fee_model->discount_type)) {
                $discount_type = ($mmb1_parking_fee_model->discount_type == 'price') ? 1 : 2;
            }

            if ($mmb1_parking_fee_model) {
                $parking = Parking::create([
                    'residence_id' => $mmb1_parking_fee_model->residence_id,
                    'type' => $mmb1_parking_fee_model->type == 'free' ? 1 : 2,
                    'rate_mode' => $mmb1_parking_fee_model->rate_mode == 'rate_per_hour' ? 1 : 2,
                    'is_discount_coupon' => $mmb1_parking_fee_model->is_discount_coupon,
                    'discount_type' => $discount_type,
                    'created_at' => $mmb1_parking_fee_model->created_at,
                    'updated_at' => $mmb1_parking_fee_model->updated_at,
                ]);

                if ($parking) {
                    $mmb1_calculations = DB::connection('mmb1')
                        ->table('calculations')
                        ->where('parking_fee_id', $mmb1_parking_fee_model->id)
                        ->get();

                    foreach ($mmb1_calculations as $mmb1_calculation) {
                        $minutes = $mmb1_calculation->free_parking_minutes;

                        $time = new DateTime;
                        $time->setTime(0, $minutes, 0);
                        $formatted_time = $time->format('H:i:s');

                        $minutes = $mmb1_calculation->chartered_duration;

                        $time = new DateTime;
                        $time->setTime(0, $minutes, 0);
                        $chartered_formatted_time = $time->format('H:i:s');

                        Calculation::create([
                            'parking_id' => $parking->id,
                            'vehicle_type' => $mmb1_calculation->vehicle_type == 'car' ? 1 : 2,
                            'is_stamp' => $mmb1_calculation->for_stamp,
                            'free_parking_minutes' => $formatted_time,
                            'rate_per_hour' => $mmb1_calculation->rate_per_hour,
                            'chartered_duration' => $chartered_formatted_time,
                            'chartered_price' => $mmb1_calculation->chartered_price,
                            'penalty' => $mmb1_calculation->penalty,
                            'created_at' => $mmb1_calculation->created_at,
                            'updated_at' => $mmb1_calculation->updated_at,
                        ]);
                    }
                }
            }

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
