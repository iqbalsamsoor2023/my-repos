<?php

namespace App\Jobs;

use App\Models\Calculation;
use App\Models\VisitorLog;
use App\Models\VisitorParking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MigrateVisitorParkingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    protected $residence_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $residence_id)
    {
        $this->onQueue('migrateVisitorDataQueue');
        $this->data = $data;
        $this->residence_id = $residence_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $value = $this->data;
        $residence_id = $this->residence_id;
        $mmb2_visitor = VisitorLog::withTrashed()->where('visitor_generated_no', $value->run_visitor_no)->where('visitor_code', $value->code)->first();
        $mmb1_calculation = DB::connection('mmb1')
            ->table('calculations')->find($value->calculation_id);
        if (isset($mmb1_calculation)) {
            $rate_per_hour = intval($mmb1_calculation->rate_per_hour ?? 0);
            $chartered_price = intval($mmb1_calculation->chartered_price ?? 0);
            $penalty = intval($mmb1_calculation->penalty ?? 0);
            $mmb2_calculation = Calculation::with('parking')
                ->whereHas('parking', function ($query) use ($residence_id) {
                    $query->where('residence_id', $residence_id);
                })->where('vehicle_type', $mmb1_calculation->vehicle_type == 'car' ? 1 : 2)
                ->where('is_stamp', $mmb1_calculation->for_stamp)->where('rate_per_hour', $rate_per_hour)
                ->where('chartered_price', $chartered_price)->where('penalty', $penalty)
                ->first();
        }

        // amount to pay calulation
        $vehicle_type = ($value->vehicle_type == 3) ? 'motorcycle' : 'car';

        if ($value->is_stamp == 1) {
            $calculation = DB::connection('mmb1')
                ->table('calculations')->where('for_stamp', 1)
                ->where('parking_fee_id', $value->parking_fee_id)
                ->where('vehicle_type', $vehicle_type)
                ->first();
        } else {
            $calculation = DB::connection('mmb1')
                ->table('calculations')->where('for_stamp', 0)
                ->where('parking_fee_id', $value->parking_fee_id)
                ->where('vehicle_type', $vehicle_type)
                ->first();
        }

        if (isset($value->depart_at)) {
            $parking_duration_in_minutes = (new Carbon($value->depart_at))->diffInMinutes(new Carbon($value->arrive_at)); // total parking duration
            $parking_duration_in_minutes = $parking_duration_in_minutes - $calculation->free_parking_minutes; // minus free parking minutes if any
            $is_qualified_for_chartered_duration = $parking_duration_in_minutes >= $calculation->chartered_duration ? true : false;

            if ($is_qualified_for_chartered_duration) {
                $parking_duration_in_minutes = $parking_duration_in_minutes - $calculation->chartered_duration;
            }

            $total_duration_parking_in_hours = self::convertParkingDurationMinutesToHours($parking_duration_in_minutes);

            // exceed 1 seconds will considered as 1 hours
            $seconds = (new Carbon($value->depart_at))->diff(new Carbon($value->arrive_at))->format('%S');
            if ($seconds >= 1) {
                $total_duration_parking_in_hours = $total_duration_parking_in_hours + 1;
            }

            $amount_to_pay = $total_duration_parking_in_hours * $calculation->rate_per_hour;
            if ($is_qualified_for_chartered_duration) {
                $amount_to_pay = $amount_to_pay + $calculation->chartered_price;
            }

            // amount to pay with discount after discount
            if ($value->discount_value != '0.00') {
                $parking_fee_models = DB::connection('mmb1')->table('parking_fee_models')->where('residence_id', $residence_id)->first();

                if ($parking_fee_models->discount_type == 'price') {
                    $amount_to_pay = $amount_to_pay - $value->discount_value;
                } else {
                    $amount_to_pay = $amount_to_pay - (($value->discount_value / 60) * $calculation->rate_per_hour);
                }
            }
        }

        if (isset($mmb2_visitor) && isset($mmb2_calculation) && isset($value->depart_at)) {
            VisitorParking::create([
                'visitor_log_id' => $mmb2_visitor->id ?? null,
                'calculation_id' => $mmb2_calculation->id,
                'discount_value' => $value->discount_value,
                'amount_to_pay' => $amount_to_pay ?? 0,  // not in mmb1, must manually calculate
                'amount_paid' => $value->amount, // this is correct
                'is_penalty' => $value->is_penalty,
                'is_stamp' => $value->is_stamp,
                'calculation_records' => $mmb2_calculation,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
            ]);
        }
    }

    private static function convertParkingDurationMinutesToHours($parking_duration_in_minutes)
    {
        // convert parking duration minutes to hour
        $hours = floor($parking_duration_in_minutes / 60); // Get the number of whole hours

        return $hours;
    }
}
