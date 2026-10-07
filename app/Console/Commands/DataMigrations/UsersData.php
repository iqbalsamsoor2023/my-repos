<?php

namespace App\Console\Commands\DataMigrations;

use App\Enums\User\RoleType;
use App\Models\Company;
use App\Models\Country;
use App\Models\Residence;
use App\Models\User;
use App\Services\CloudObjectStorageService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UsersData
{
    public function execute($residence_id)
    {
        try {
            DB::beginTransaction();

            $disk = Storage::disk('cos2');

            $old_residence = DB::connection('mmb1')
                ->table('residences')
                ->where('id', $residence_id)
                ->first();

            // sales_manager

            // property_management (pass)
            $property_management = DB::connection('mmb1')
                ->table('users')
                ->select('*', 'users.id as id')
                ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
                ->join('residence_managements', 'residence_managements.user_id', '=', 'users.id')
                ->where('residence_managements.residence_id', $residence_id)
                ->first();
            $user = User::create($this->mmb2UserData($property_management));
            $property_management_user_id = $user->id;
            $user->assignRole(RoleType::PROPERTY_MANAGEMENT->value);

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

            if (User::withTrashed()->where('email', $developer->email)->doesntExist()) {
                $user = User::create($this->mmb2UserData($developer));
                $developer_user_id = $user->id;
                $user->assignRole(RoleType::DEVELOPER->value);

                $cosClient = CloudObjectStorageService::execute();

                $bucket = 'mooban-1258956757'; // Bucket name in the format of BucketName-APPID
                $result = $cosClient->listObjects([
                    'Bucket' => $bucket,
                    'Prefix' => config('app.path.cos')."/user/$developer->id/",
                ]);

                $images = [];
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $rt) {
                        $images[] = $rt['Key'];
                    }
                }

                foreach ($images as $image) {
                    if ($disk->has($image)) {
                        $url = $disk->url($image);
                        $user->addMediaFromUrl($url);
                    }
                }

                Company::where('name', $residence->developer_name)->update([
                    'user_id' => $user->id,
                ]);
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

                $user = User::create($this->mmb2UserData($receptionist));
                $receptionist_user_id = $user->id;
                $user->assignRole(RoleType::RECEPTIONIST->value);
            } else {
                $receptionist_user_id = null;
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
                if (User::withTrashed()->where('email', $resident->email)->doesntExist()) {
                    $user = User::create($this->mmb2UserData($resident));
                } else {
                    $user = User::withTrashed()->where('email', $resident->email)->first();
                }

                $role_id = DB::connection('mmb1')
                    ->table('model_has_roles')
                    ->where('model_id', $resident->id)
                    ->whereIn('role_id', [5, 6])
                    ->value('role_id');

                if ($role_id == 5) { // resident
                    $user->assignRole(RoleType::UNIT_OWNER->value);
                    if ($user->type_id = 2) {
                        $user->assignRole(RoleType::UNIT_TENANT->value);
                    }
                } elseif ($role_id == 6) {
                    $user->assignRole(RoleType::UNIT_TENANT->value);
                } else {
                    Log::info('Alien detected(No role). Something Wrong. Please check this id in old user db: '.$resident->id);
                }
            }

            // accountant user
            $accountant = DB::connection('mmb1')
                ->table('residence_accountants')
                ->select('*', 'users.id as id', 'users.created_at as created_at', 'users.updated_at as updated_at')
                ->leftJoin('users', 'users.id', 'residence_accountants.user_id')
                ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
                ->where('residence_accountants.residence_id', $residence_id)->first();

            if (! empty($residents)) {
                $accountant = User::create($this->mmb2UserData($accountant));
                $user->assignRole(RoleType::ACCOUNTANT->value);
            }

            // update residence table
            Residence::where('id', $old_residence->id)->update([
                'developer_user_id' => $developer_user_id ?? null,
                'property_management_user_id' => $property_management_user_id ?? null,
                'receptionist_user_id' => $receptionist_user_id ?? null,
                'accountant_user_id' => $accountant->id ?? null,
            ]);

            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public static function mmb2UserData($data)
    {
        $user_country = DB::connection('mmb1')
            ->table('countries')
            ->where('id', $data->nationality)
            ->first();

        $country_id = Country::where('name', $user_country->name ?? 0)->value('id');

        $pdpa_agreed_at = null;
        $resident_agreement = DB::connection('mmb1')
            ->table('resident_agreements')
            ->where('user_id', $data->id)
            ->first();
        if (isset($resident_agreement)) {
            $pdpa_agreed_at = $resident_agreement->agreed_at == '0000-00-00 00:00:00' ? null : $resident_agreement->agreed_at;
        }

        $verify_user = DB::connection('mmb1')
            ->table('verify_users')
            ->where('user_id', $data->id)
            ->first();

        // dob (2 sources of truth : user profiles and user healths)
        $user_health = DB::connection('mmb1')
            ->table('user_healths')
            ->where('user_id', $data->id)
            ->first();

        $gender = $data->gender == 0 ? null : $data->gender;

        if (empty($gender)) {
            $gender = isset($user_health) ? $user_health->gender : null;
        }

        $dob = null;
        if (isset($data->date_of_birth)) {
            $dob = $data->date_of_birth;
        } elseif (isset($user_health->date_of_birth)) {
            $dob = $user_health->date_of_birth;
        }

        $mmb2Data = [
            'country_id' => $country_id ?? null,
            'name' => $data->name,
            'email' => $data->email,
            // 'email_verified_at' => $verify_user->created_at ?? null,
            'email_verified_at' => $data->verified == 1 ? $verify_user->created_at ?? null : null,
            'pdpa_agreed_at' => $pdpa_agreed_at,
            'id_number' => $data->id_number ?? null,
            'password' => $data->password,
            'phone_no' => $data->contact_no ?? 0,
            'is_community_head_verified' => ($data->ch_verified == 1 || $data->ch_verified == 2) ? Carbon::today() : null,
            'date_of_birth' => $dob ?? null,
            'gender' => $gender ?? null,
            'passport_number' => $data->passport_number ?? null,
            'passport_expiry' => $data->passport_exp_date ?? null,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
            'deleted_at' => $data->deleted_at,
        ];

        return $mmb2Data;
    }
}
