<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnitsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('residence_units')
                ->where('residence_id', $residence_id)
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $unit = Unit::withoutEvents(function () use ($value) {
                            $creted_unit = Unit::create([
                                'residence_id' => $value->residence_id,
                                'invitation_code_owner' => $value->invitation_code_owner,
                                'invitation_code_tenant' => $value->invitation_code_tenant,
                                'home_id' => $value->home_id,
                                'unit_size' => $value->space,
                                'myseevr_link' => $value->info_link,
                                'unit_number' => $value->unit,
                                'street' => $value->street ?? '',
                                'floor' => $value->floor,
                                'block' => $value->block,
                                'status' => $value->living_status,
                                'move_in_at' => $value->transfer_home_date,
                                'created_at' => $value->created_at,
                                'deleted_at' => $value->deleted_at,
                            ]);

                            return $creted_unit;
                        });

                        $residennce_users = DB::connection('mmb1')
                            ->table('residence_users')
                            ->where('residence_unit_id', $value->id)
                            ->get();

                        // unit user
                        foreach ($residennce_users as $residence_user) {
                            $user = DB::connection('mmb1')
                                ->table('users')
                                ->where('id', $residence_user->user_id)
                                ->orderBy('id', 'asc')
                                ->first();

                            $mmb2_user = User::withTrashed()->where('email', $user->email)->first();
                            if ($mmb2_user) {
                                $relationship = null;
                                $is_owner = 1;
                                $is_main_owner = 0;
                                $is_main_tenant = 0;
                                if ($residence_user->type_id == 1) {
                                    $is_owner = 1;
                                } elseif ($residence_user->type_id == 2) {
                                    $is_owner = 0;
                                }

                                if (isset($residence_user->relationship)) {
                                    if ($residence_user->relationship == 0) {
                                        $is_main_owner = 1;
                                    } elseif ($residence_user->relationship == 11) {
                                        $is_main_tenant = 1;
                                    } else {
                                        $relationship = $residence_user->relationship;
                                    }
                                }

                                $unit_user = [
                                    'unit_id' => $unit->id,
                                    'user_id' => $mmb2_user->id,
                                    'is_owner' => $is_owner,
                                    'mmb_id' => $residence_user->mmb_id,
                                    'is_main_owner' => $is_main_owner,
                                    'is_main_tenant' => $is_main_tenant,
                                    'relationship' => $relationship ?? null,
                                    'approval_status' => 1,
                                    'created_at' => $residence_user->created_at,
                                    'updated_at' => $residence_user->updated_at,
                                    'deleted_at' => $residence_user->deleted_at,
                                ];
                                UnitUser::withoutEvents(function () use ($unit_user) {
                                    return UnitUser::create($unit_user);
                                });
                            } else {
                                Log::info('Alien detected. Pls Check');
                            }
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
