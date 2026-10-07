<?php

namespace App\Console\Commands\DataMigrations;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompaniesData
{
    public static function execute($residence_id)
    {
        self::developers(); // migrate all cause one company might has more than one mooban

        // self::propertyManagements();
        // self::sgCompanies();
        // self::vehicleInsuranceCompanies();
    }

    private static function propertyManagements(): bool
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('property_managements')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = [
                'old_id' => $value->id,
                'province_id' => null,
                'user_id' => null,
                'type' => 'Property Management',
                'name' => $value->name,
                'name_th' => $value->name_th,
                'contact_email' => $value->email,
                'contact_number' => $value->contact_no,
                'address' => $value->address,
                'person_in_charges' => json_encode([$value->person_in_charge]),
                'website_url' => $value->company_website,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
                'deleted_at' => $value->deleted_at,
            ];
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            Company::insert($chunk);
        }

        return true;
    }

    private static function developers(): bool
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('developers')
            ->leftJoin('developer_users', 'developer_users.developer_id', 'developers.id')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = [
                'province_id' => null,
                'user_id' => $value->user_id,
                'type' => 'Developer',
                'name' => $value->name,
                'contact_email' => $value->email,
                'contact_number' => $value->contact_no,
                'website_url' => $value->website_link,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
            ];
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            Company::insert($chunk);
        }

        return true;
    }

    // private static function sgCompanies(): bool
    // {
    //     $mmb1Data = DB::connection('mmb1')
    //         ->table('security_guards')
    //         ->get();

    //     $mmb2Data = [];

    //     foreach ($mmb1Data as $key => $value) {
    //         $mmb2Data[] = [
    //             'old_id' => $value->id,
    //             'type' => 'Security Guard',
    //             'name' => $value->name,
    //             'name_th' => $value->name_th,
    //             'contact_email' => $value->email,
    //             'contact_number' => $value->company_contact_no,
    //             'address' => $value->address,
    //             'person_in_charges' => json_encode([
    //                 'name' => $value->person_in_charge,
    //             ]),
    //             'website_url' => $value->company_website,
    //             'created_at' => $value->created_at,
    //             'updated_at' => $value->updated_at,
    //             'deleted_at' => $value->deleted_at,
    //         ];
    //     }

    //     foreach (array_chunk($mmb2Data, 1000) as $chunk) {
    //         Company::insert($chunk);
    //     }

    //     return true;
    // }

    private static function vehicleInsuranceCompanies(): bool
    {
        $mmb1Data = DB::connection('mmb1')
            ->table('vehicle_insurance_company')
            ->get();

        $mmb2Data = [];

        foreach ($mmb1Data as $key => $value) {
            $mmb2Data[] = [
                'old_id' => $value->id,
                'type' => 'Vehicle Insurance',
                'name' => $value->name,
                'name_th' => $value->name_th,
                'contact_number' => $value->emergency_contact,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
            ];
        }

        foreach (array_chunk($mmb2Data, 1000) as $chunk) {
            Company::insert($chunk);
        }

        return true;
    }
}
