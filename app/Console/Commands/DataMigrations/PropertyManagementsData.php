<?php

namespace App\Console\Commands\DataMigrations;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Residence;
use App\Models\SubscriptionExpire;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PropertyManagementsData
{
    public static function execute($residence_id)
    {
        try {
            DB::connection('mmb1')->table('pm_operation_centers')
                ->select(DB::raw('*,pm_operation_centers.id, residence_details.residence_id as residence_id, users.name as username, users.id as user_id, users.email,property_managements.email as pic_email, property_managements.contact_no as pic_contact_no, property_managements.name as company_name, property_managements.name_th as company_name_th, pm_operation_centers.status, users.created_at as created_at, users.updated_at as updated_at'))
                ->join('users', 'users.id', '=', 'pm_operation_centers.user_id')
                ->join('property_managements', 'property_managements.id', '=', 'pm_operation_centers.property_management_id')
                ->leftJoin('residence_details', 'residence_details.property_management_id', 'property_managements.id')
                ->where('pm_operation_centers.status', '=', '1')
                ->orderBy('property_managements.id')
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $key => $value) {
                        $company = Company::where('name', $value->company_name)->where('type', 'Property Management')->first();
                        $pmoc_user = DB::connection('mmb1')->table('users')->where('id', $value->user_id)->first();
                        $user = User::withTrashed()->where('email', $pmoc_user->email)->first();

                        $person_in_charges = null;
                        if (isset($value->person_in_charge)) {
                            $person_in_charges[] = [
                                'name' => $value->person_in_charge,
                                'email' => $value->pic_email,
                                'contact' => $value->pic_contact_no,
                                'position' => null,
                            ];
                        }

                        if (! isset($company)) {
                            $company = Company::withoutEvents(function () use ($user, $value, $person_in_charges) {
                                return Company::create([
                                    'province_id' => null,
                                    'user_id' => $user->id ?? null,
                                    'type' => 'Property Management',
                                    'name' => $value->name,
                                    'name_th' => $value->name_th,
                                    'person_in_charges' => $person_in_charges ?? null,
                                    'address' => $value->address,
                                    'contact_email' => $value->email,
                                    'contact_number' => $value->company_contact_no,
                                    'website_url' => $value->company_website,
                                    'created_at' => $value->created_at ?? now(),
                                    'updated_at' => $value->updated_at ?? now(),
                                ]);
                            });
                        }

                        Residence::where('id', $value->residence_id)->update([
                            'property_management_id' => $company->id,
                        ]);

                        // pm expired at
                        $residence_detail = json_decode($value->value);
                        $expired_date = null;
                        $residence_exists = Residence::where('id', $value->residence_id)->exists();
                        if (isset($residence_detail->pm) && $residence_exists) {
                            $expired_date = Carbon::parse($residence_detail->pm->expire_at)->format('Y-m-d');

                            SubscriptionExpire::create([
                                'type' => 'Residence',
                                'company_id' => $company->id,
                                'residence_id' => $value->residence_id,
                                'expiry_date' => $expired_date.' 00:00:00',
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
