<?php

namespace App\Console\Commands\DataMigrations;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class FacilityTimeslotsData
{
    public static function execute($residence_id)
    {
        try {
            $mmb1Data = DB::connection('mmb1')
                ->table('facility_timeslots')
                ->select('*', 'facility_timeslots.id as id', 'facility_timeslots.status as status')
                ->leftJoin('facilities', 'facilities.id', 'facility_timeslots.facility_id')
                ->where('facilities.residence_id', $residence_id)
                ->orderBy('facility_timeslots.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {

                        // facility_id
                        $mmb1_facility = DB::connection('mmb1')
                            ->table('facilities')
                            ->where('id', $value->facility_id)
                            ->first();

                        $mmb2_facility = DB::table('facilities')
                            ->where('name', $mmb1_facility->name)
                            ->where('residence_id', $mmb1_facility->residence_id)->first();

                        $day = null;
                        if ($value->day == 1) {
                            $day = Carbon::MONDAY;
                        } elseif ($value->day == 2) {
                            $day = Carbon::TUESDAY;
                        } elseif ($value->day == 3) {
                            $day = Carbon::WEDNESDAY;
                        } elseif ($value->day == 4) {
                            $day = Carbon::THURSDAY;
                        } elseif ($value->day == 5) {
                            $day = Carbon::FRIDAY;
                        } elseif ($value->day == 6) {
                            $day = Carbon::SATURDAY;
                        } elseif ($value->day == 7) {
                            $day = Carbon::SUNDAY;
                        }

                        DB::table('facility_timeslots')->insert([
                            'facility_id' => $mmb2_facility->id,
                            'day' => $day,
                            'start_at' => $value->start_at,
                            'end_at' => $value->end_at,
                            'is_active' => $value->status,
                        ]);

                        DB::commit();
                    }
                });

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
