<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\DB;

class DevelopersData
{
    /**
     * migrate all developer coz one developer has many
     */
    public static function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('developers')
                ->get();

            foreach ($mmb1Data as $key => $value) {
                $person_in_charges = null;
                $developer_users = DB::connection('mmb1')->table('developer_users')->leftJoin('users', 'users.id', 'developer_users.user_id')->where('developer_users.id', $value->id)->latest('developer_users.created_at')->first();

                // dd($developer_users);
                $person_in_charges[] = [
                    'name' => $developer_users->name,
                    'email' => $developer_users->email,
                    'contact' => $developer_users->contact_no,
                    'position' => null,
                ];

                $company = Company::create([
                    'province_id' => null, // no province id old db (done)
                    'user_id' => null, // update when creating developer user (done)
                    'type' => 'Developer',
                    'name' => $value->name,
                    'name_th' => $value->name,
                    'contact_email' => $value->email,
                    'person_in_charges' => $person_in_charges,
                    'contact_number' => $value->contact_no,
                    'website_url' => $value->website_link,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at,
                ]);
            }
            DB::commit();

            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
