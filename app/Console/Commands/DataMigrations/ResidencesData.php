<?php

namespace App\Console\Commands\DataMigrations;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Residence;
use App\Models\SubscriptionExpire;
use Exception;
use Illuminate\Support\Facades\DB;

class ResidencesData
{
    // 489,538,2591,3274,3989,3997,4014,2475,2509,2530,2556,2558,2623,3266,3283,3314,2338,2559,3275,4030,1336,3631,
    // 3994,4024,4025,4029,4031,1132,2506,3839,3995,3281,3318,915,1754,2507,2557,2588,2610,2611,3191,3520,4018,
    // 2218,2259,2612,3277,3404,3488,3944,304,305,312,495,645,678,741,1859
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $datas = DB::connection('mmb1')
                ->table('residences')
                ->select('*', 'residences.id as id', 'residences.created_at as created_at', 'residences.updated_at as updated_at', 'residences.deleted_at as deleted_at')
                ->leftJoin('residence_details', 'residence_details.residence_id', 'residences.id')
                ->whereIn('residences.id', ['4240', '4241'])
                ->get();

            foreach ($datas as $data) {
                $developer_name = DB::connection('mmb1')
                    ->table('developers')
                    ->where('id', $data->developer_id)
                    ->value('name');

                $developer_id = Company::where('type', 'Developer')
                    ->where('name', $developer_name)
                    ->value('id');

                $residence_company = null;
                $residence_detail = json_decode($data->value);
                if (isset($residence_detail->co)) {
                    if (isset($residence_detail->co->company_name)) {
                        $residence_company = Company::firstOrCreate([
                            'type' => 'Residence',
                            'name' => $residence_detail->co->company_name,
                            'contact_number' => $residence_detail->co->company_telephone_number,
                            'address' => $residence_detail->co->company_address,
                        ]);
                    }
                }

                // fire insurance in mmb1
                $fire_insurance_company = null;
                if (isset($residence_detail->fi->company_name)) {
                    $fire_insurance_company = Company::firstOrCreate([
                        'type' => 'Fire Insurance',
                        'name' => $residence_detail->fi->company_name,
                        'contact_number' => null,
                        'address' => null,
                    ]);
                }

                // subscription date and time
                $property_management_user = null;
                $property_management_user = DB::connection('mmb1')
                    ->table('users')
                    ->select('*', 'users.id as id')
                    // ->leftJoin('user_profiles', 'user_profiles.user_id', 'users.id')
                    ->join('residence_managements', 'residence_managements.user_id', '=', 'users.id')
                    ->where('residence_managements.residence_id', $data->id)
                    ->first();

                $residence = Residence::withoutEvents(function () use ($data, $developer_id, $residence_company, $fire_insurance_company, $property_management_user) {
                    if (Residence::withTrashed()->where('name', $data->name)->doesntExist()) {
                        return Residence::create([
                            'id' => $data->id,
                            'name' => $data->name,
                            'name_th' => $data->name_th,
                            'mooban_type' => $data->mooban_type ?? 'Single home',
                            'type' => $data->type,
                            'completion_year' => $data->completion_year == 1900 ? 1901 : $data->completion_year, // mysql cannot insert year 1900 and below (data type YEAR)
                            'latitude' => $data->latitude,
                            'longitude' => $data->longitude,
                            'subdistrict_id' => $data->subdistrict_id,
                            'developer_id' => $developer_id,
                            'developer_user_id' => null, // will update when creating developer users(done)
                            'property_management_type' => isset(json_decode($data->value)->pm_type) ? json_decode($data->value)->pm_type : null,
                            'property_management_id' => null, // will update when creating developer company
                            'property_management_user_id' => null, // will update when creating developer users(done),
                            'receptionist_user_id' => null, // will update when creating developer users(done),
                            'accountant_user_id' => null, // not yet
                            'sgoc_company_id' => null, // will update when creating sgoc company
                            'sgoc_residence_guard_user_id' => null, // will update when creating sc company
                            'company_insurance_id' => $fire_insurance_company->id ?? null,
                            'company_id' => $residence_company->id ?? null,
                            'is_active' => (bool) $data->status,
                            'is_demo' => $data->demo_status,
                            'subscription_start_date' => $property_management_user->start_at ?? now(),
                            'subscription_end_date' => $property_management_user->expire_at ?? now()->addYear(),
                            'support_ticket_status' => 1,
                            'created_at' => $data->created_at,
                            'updated_at' => $data->updated_at,
                            'deleted_at' => $data->deleted_at,
                            'residence_activation_status_id' => 5,
                        ]);
                    }
                });

                // fire insurance expired at
                $expired_date = null;
                if (isset($residence_detail->fi) && isset($fire_insurance_company) && isset($residence->id)) {
                    $expired_date = Carbon::parse($residence_detail->fi->expire_at)->format('Y-m-d');
                    SubscriptionExpire::create([
                        'type' => 'Residence',
                        'company_id' => $fire_insurance_company->id,
                        'residence_id' => $residence->id,
                        'expiry_date' => $expired_date.' 00:00:00',
                    ]);
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
