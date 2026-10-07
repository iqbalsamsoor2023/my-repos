<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Pet;
use App\Models\Unit;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PetsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('pets')
                ->select('*', 'pets.id as id', 'pets.created_at as created_at', 'pets.updated_at as updated_at', 'pets.deleted_at as deleted_at')
                ->leftJoin('residence_units', 'residence_units.id', 'pets.residence_unit_id')
                ->where('residence_units.residence_id', $residence_id)
                ->orderBy('pets.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
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

                        // user
                        if (isset($value->residence_user_id)) {
                            $mmb1_user = DB::connection('mmb1')
                                ->table('users')
                                ->leftJoin('residence_users', 'residence_users.user_id', 'users.id')
                                ->where('residence_users.id', $value->residence_user_id)
                                ->first();
                            $user = User::withTrashed()->where('email', $mmb1_user->email)->first();
                        }

                        // user
                        if (isset($value->created_by)) {
                            $mmb1_user = DB::connection('mmb1')
                                ->table('users')
                                ->leftJoin('residence_users', 'residence_users.user_id', 'users.id')
                                ->where('users.id', $value->created_by)
                                ->first();
                            $created_by = User::withTrashed()->where('email', $mmb1_user->email)->first();
                        }

                        $pet = Pet::withoutEvents(function () use ($mmb2_unit, $user, $value, $created_by) {
                            Pet::create([
                                'unit_id' => $mmb2_unit->id ?? null,
                                'user_id' => $user->id ?? null,
                                'breed' => $value->breed,
                                'type' => $value->type == 1 ? 1 : 2,
                                'year' => $value->year,
                                'generated_pet_no' => $value->pets_id,
                                'created_by' => $created_by->id,
                                'created_at' => $value->created_at,
                                'updated_at' => $value->updated_at,
                                'deleted_at' => $value->deleted_at,
                            ]);
                        });
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
