<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\User;
use App\Models\UserHealth;
use Exception;
use Illuminate\Support\Facades\DB;

class UserHealthsData
{
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1_users = DB::connection('mmb1')
                ->table('users')
                ->select('*', 'users.id as id', 'users.created_at as created_at', 'users.updated_at as updated_at', 'users.deleted_at as deleted_at')
                ->leftJoin('residence_users', 'residence_users.user_id', '=', 'users.id')
                ->where('residence_users.residence_id', $residence_id)
                ->get();

            foreach ($mmb1_users as $mmb1_user) {
                $mmb2_user = User::withTrashed()->where('email', $mmb1_user->email)->first();

                $mmb1_user_health = DB::connection('mmb1')
                    ->table('user_healths')->where('user_id', $mmb1_user->id)
                    ->latest()
                    ->first();

                $user_health_exists = UserHealth::where('user_id', $mmb2_user->id)->exists();
                if (! $user_health_exists) {
                    if (isset($mmb1_user_health)) {
                        UserHealth::create([
                            'user_id' => $mmb2_user->id,
                            'blood_type' => null,
                            'height' => $mmb1_user_health->height,
                            'weight' => $mmb1_user_health->weight,
                            'health_questionnaire_answers' => $mmb1_user_health->questionnaire ?? null,
                            'created_at' => $mmb1_user_health->created_at,
                            'updated_at' => $mmb1_user_health->updated_at,
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
