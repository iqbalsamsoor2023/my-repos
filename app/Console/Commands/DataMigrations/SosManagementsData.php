<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\SosManagement;
use App\Models\Unit;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class SosManagementsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('emergency_alarms')
                ->select('*', 'emergency_alarms.id as id', 'emergency_alarms.user_id as user_id', 'emergency_alarms.created_at as created_at', 'emergency_alarms.updated_at as updated_at', 'emergency_alarms.type_id as type_id', 'emergency_alarms.status as status')
                ->leftJoin('residences', 'emergency_alarms.residence_id', '=', 'residences.id')
                ->leftJoin('residence_users', 'emergency_alarms.residence_user_id', '=', 'residence_users.id')
                ->leftJoin('residence_units', 'residence_units.ID', '=', 'residence_users.residence_unit_id')
                ->leftJoin('users', 'emergency_alarms.user_id', '=', 'users.id')
                ->where('emergency_alarms.residence_id', $residence_id)
                ->orderBy('emergency_alarms.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $value) {
                        // $mmb1_sos_management = DB::connection('mmb1')->table('sos_managements')->where('sos_alert_id', $value->id)->first();
                        // $residence_user = DB::connection('mmb1')->table('residence_users')->where('id', $value->residence_user_id)->first();

                        // user
                        if (isset($value->user_id)) {
                            $mmb1_user = DB::connection('mmb1')
                                ->table('users')
                                ->where('id', $value->user_id)
                                ->first();
                            $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email)->first();
                        }

                        // unit_id
                        if (isset($value->residence_unit_id)) {
                            $mmb1_unit = DB::connection('mmb1')
                                ->table('residence_units')
                                ->where('id', $value->residence_unit_id)
                                ->first();

                            $mmb2_unit = Unit::withTrashed()->where('home_id', $mmb1_unit->home_id)
                                ->where('unit_number', $mmb1_unit->unit)
                                ->first();
                        }

                        // //accepted_by
                        // if (isset($mmb1_sos_management->accepted_by)) {
                        //     $mmb1_user = DB::connection('mmb1')
                        //         ->table('users')
                        //         ->where('id', $mmb1_sos_management->accepted_by)
                        //         ->first();
                        //     if (isset($mmb1_user->email)) {
                        //         $accepted_by = DB::connection('sgoc')->table('users')->where('email', $mmb1_user->email)->first();
                        //     }
                        // }

                        $remark = null;
                        $status = null;
                        if ($value->type_id == 4) {
                            $remark = 'Sorry I pressed wrong';
                            $status = 3;
                        } elseif ($value->type_id == 5) {
                            $remark = "Thank You. I'm safe now";
                            $status = 4;
                        }

                        if (isset($mmb2_unit)) {
                            SosManagement::create([
                                'created_by_id' => $mmb2_user->id ?? null,
                                'unit_id' => $mmb2_unit->id,
                                'accepted_by' => null,
                                'user_action_request' => in_array($value->type_id, [1, 2]) ? $value->type_id : null, // mmb1 has 5 types, mmb2 has 2 types
                                'longitude' => null,
                                'latitude' => null,
                                'status' => isset($status) ? $status : $value->status,
                                'remark' => $remark,
                                'created_at' => $value->created_at,
                                'updated_at' => $value->updated_at,
                            ]);
                        }
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
