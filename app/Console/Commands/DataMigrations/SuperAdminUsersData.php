<?php

namespace App\Console\Commands\DataMigrations;

use App\Enums\User\RoleType;
use App\Models\Country;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class SuperAdminUsersData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('users')
                ->leftJoin('model_has_roles', 'model_has_roles.model_id', 'users.id')
                ->where('model_has_roles.role_id', 1) // admin
                ->get();

            $mmb1Data = DB::connection('mmb1')
                ->table('users')
                ->leftJoin('model_has_roles', 'model_has_roles.model_id', 'users.id')
                ->where('model_has_roles.role_id', 1) // admin
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $user_country = DB::connection('mmb1')
                            ->table('countries')
                            ->where('id', $value->nationality)
                            ->first();

                        $country_id = Country::where('name', $user_country->name ?? 0)->value('id');

                        $user_exists = User::withTrashed()->where('email', $value->email)->first();
                        if (! $user_exists) {
                            $user = User::create([
                                'country_id' => $country_id ?? null,
                                'name' => $value->name,
                                'email' => $value->email,
                                // 'email_verified_at' => null, // not sure where to retrieve
                                'id_number' => $value->id_number ?? null,
                                'password' => $value->password,
                                'phone_no' => $value->contact_no ?? 0,
                                // 'address' => null, // no data
                                'is_community_head_verified' => ($value->ch_verified == 1 || $value->ch_verified == 2) ? Carbon::today() : null,
                                'date_of_birth' => $value->date_of_birth ?? null,
                                'gender' => $value->gender ?? null,
                                'passport_number' => $value->passport_number ?? null,
                                'passport_expiry' => $value->passport_exp_date ?? null,
                                'created_at' => $value->created_at,
                                'updated_at' => $value->updated_at,
                                'deleted_at' => $value->deleted_at,
                            ]);
                            $user->assignRole(RoleType::SUPER_ADMIN->value);
                        } else {
                            $user_exists->assignRole(RoleType::SUPER_ADMIN->value);
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
