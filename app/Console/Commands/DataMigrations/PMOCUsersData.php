<?php

namespace App\Console\Commands\DataMigrations;

use App\Enums\User\RoleType;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PMOCUsersData
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute()
    {
        try {
            $sgocs = DB::connection('mmb1')
                ->table('pm_operation_centers')
                ->select('*', 'users.id as user_id', 'property_managements.created_at as pm_created_at', 'property_managements.updated_at as pm_updated_at')
                ->join('users', 'users.id', '=', 'pm_operation_centers.user_id')
                ->join('property_managements', 'property_managements.id', '=', 'pm_operation_centers.property_management_id')
                ->where('pm_operation_centers.status', '=', '1')->get();

            foreach ($sgocs as $sgoc) {
                $pmoc = DB::connection('mmb1')
                    ->table('users')
                    ->select('*', 'users.id as id')
                    ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
                    ->where('users.id', $sgoc->user_id)
                    ->first();

                if (User::withTrashed()->where('email', $pmoc->email)->doesntExist()) {
                    $user = User::create(UsersData::mmb2UserData($pmoc));
                    $user->assignRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value);
                } else {
                    $user = User::withTrashed()->where('email', $pmoc->email)->first();
                    if (! in_array('Property Management Operation Center', (array) $user->roles)) {
                        $user->assignRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value);
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
