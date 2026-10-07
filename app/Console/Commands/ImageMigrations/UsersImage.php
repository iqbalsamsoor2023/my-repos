<?php

namespace App\Console\Commands\ImageMigrations;

use App\Jobs\MigrateFilesJobs;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UsersImage
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function execute($residence_id)
    {
        $mmb1_bucket = Storage::disk('cos2');
        $mmb2_bucket = Storage::disk('cos');

        // sales_manager

        // property_management (pass)
        $property_management = DB::connection('mmb1')
            ->table('users')
            ->select('*', 'users.id as id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
            ->join('residence_managements', 'residence_managements.user_id', '=', 'users.id')
            ->where('residence_managements.residence_id', $residence_id)
            ->first();
        $user = User::withTrashed()->where('email', $property_management->email)->first();
        $path = config('app.path.cos')."/user/$property_management->id/$property_management->avatar";

        // foreach ($images as $image) {
        if ($mmb1_bucket->has($path)) {
            $file_name = basename($path);
            $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/user/$user->id/$file_name");

            if (! $mmb2_image_exist) {
                $url = $mmb1_bucket->url($path);
                MigrateFilesJobs::dispatch($url, $user, null, null);
            }
        }
        // }

        // property management operation center (pass)
        $pmoc_id = DB::connection('mmb1')
            ->table('pmoc_residences')
            ->where('residence_id', $residence_id)->latest()->value('pm_operation_center_id');

        $pmoc = DB::connection('mmb1')
            ->table('users')
            ->select('*', 'users.id as id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
            ->join('pm_operation_centers', 'pm_operation_centers.user_id', '=', 'users.id')
            ->where('pm_operation_centers.id', $pmoc_id)
            ->first();

        if (isset($pmoc->email) && User::withTrashed()->where('email', $pmoc->email)->exists()) {
            $user = User::withTrashed()->where('email', $pmoc->email)->first();
            $path = config('app.path.cos')."/user/$pmoc->id/$pmoc->avatar";

            // foreach ($images as $image) {
            if ($mmb1_bucket->has($path)) {
                $file_name = basename($path);
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/user/$user->id/$file_name");

                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($path);
                    MigrateFilesJobs::dispatch($url, $user, null, null);
                }
            }
            // }
        }

        // developer
        $residence = DB::connection('mmb1')
            ->table('residences')
            ->select(
                '*',
                'residences.name as name',
                'developers.name as developer_name',
                'residences.id as id',
                'developers.id as developer_id',
            )
            ->leftJoin('developers', 'developers.id', 'residences.developer_id')
            ->where('residences.id', $residence_id)
            ->first();

        $developer = DB::connection('mmb1')
            ->table('users')
            ->select('*', 'users.id as id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
            ->leftJoin('developer_users', 'developer_users.user_id', 'users.id')
            ->where('developer_users.developer_id', $residence->developer_id)
            ->first();

        if (User::withTrashed()->where('email', $developer->email)->exists()) {
            $user = User::withTrashed()->where('email', $developer->email)->first();
            $path = config('app.path.cos')."/user/$developer->id/$developer->avatar";

            // foreach ($images as $image) {
            if ($mmb1_bucket->has($path)) {
                $file_name = basename($path);
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/user/$user->id/$file_name");

                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($path);
                    MigrateFilesJobs::dispatch($url, $user, null, null);
                }
            }
            // }
        }

        // receptionist
        $receptionist_user_id = DB::connection('mmb1')
            ->table('residences')
            ->where('id', $residence_id)
            ->value('receptionist_id');

        if ($receptionist_user_id != 0) { // 0 means no receptionist
            $receptionist = DB::connection('mmb1')
                ->table('users')
                ->select('*', 'users.id as id')
                ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
                ->where('users.id', $receptionist_user_id)
                ->first();
            $user = User::withTrashed()->where('email', $receptionist->email)->first();
            $path = config('app.path.cos')."/user/$receptionist->id/$receptionist->avatar";

            // foreach ($images as $image) {
            if ($mmb1_bucket->has($path)) {
                $file_name = basename($path);
                $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/user/$user->id/$file_name");

                if (! $mmb2_image_exist) {
                    $url = $mmb1_bucket->url($path);
                    MigrateFilesJobs::dispatch($url, $user, null, null);
                }
            }
            // }
        }

        // resident and tenant
        $residents = DB::connection('mmb1')
            ->table('users')
            ->select('*', 'users.id as id', 'users.created_at as created_at', 'users.updated_at as updated_at', 'users.deleted_at as deleted_at')
            ->leftJoin('residence_users', 'residence_users.user_id', '=', 'users.id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
            ->where('residence_users.residence_id', $residence_id)
            ->orderBy('users.id', 'asc')
            ->get();

        foreach ($residents as $resident) {
            if (User::withTrashed()->where('email', $resident->email)->exists()) {
                $user = User::withTrashed()->where('email', $resident->email)->first();
                $path = config('app.path.cos')."/user/$resident->id/$resident->avatar";

                // foreach ($images as $image) {
                if ($mmb1_bucket->has($path)) {
                    $file_name = basename($path);
                    $mmb2_image_exist = $mmb2_bucket->exists(config('app.path.cos')."/user/$user->id/$file_name");

                    if (! $mmb2_image_exist) {
                        $url = $mmb1_bucket->url($path);
                        MigrateFilesJobs::dispatch($url, $user, null, null);
                    }
                }
                // }
            }
        }
    }
}
