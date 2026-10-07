<?php

namespace App\Console\Commands\DataMigrations;

use App\Enums\EmergencyContact\DepartmentType;
use App\Models\DistrictEmergencyContact;
use App\Models\EmergencyContact;
use Exception;
use Illuminate\Support\Facades\DB;

class EmergencyContactsData
{
    public static function execute()
    {
        try {
            DB::beginTransaction();

            $mmb1Data = DB::connection('mmb1')
                ->table('emergency_contacts')
                ->orderBy('emergency_contacts.id')
                ->chunk(1000, function ($datas) {
                    $department_type = null;
                    foreach ($datas as $key => $value) {
                        if ($value->type_id == 1) {
                            $department_type = DepartmentType::HOSPITAL->value;
                        } elseif ($value->type_id == 2) {
                            $department_type = DepartmentType::POLICE->value;
                        } elseif ($value->type_id == 3) {
                            $department_type = DepartmentType::FOUNDATION->value;
                        } elseif ($value->type_id == 4) {
                            $department_type = DepartmentType::FIRE_STATION->value;
                        } elseif ($value->type_id == 5) {
                            $department_type = DepartmentType::OTHERS->value;
                        }

                        $emergency_contact = EmergencyContact::create([
                            'department_type' => $department_type,
                            'name' => $value->name,
                            'contact_no' => $value->contact_no,
                            'coverage_mode' => $value->coverage ?? 2,
                            'is_active' => $value->status,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                            'deleted_at' => $value->deleted_at,
                        ]);

                        $district_ids = null;
                        $district_ids = json_decode($value->district_id);

                        if (! empty($district_ids)) {
                            foreach ($district_ids as $district_id) {
                                $district_emergency_contact = DistrictEmergencyContact::insert([
                                    'emergency_contact_id' => $emergency_contact->id,
                                    'thailand_district_id' => $district_id,
                                    'created_at' => $emergency_contact->created_at,
                                    'updated_at' => $emergency_contact->updated_at,
                                    'deleted_at' => $emergency_contact->deleted_at,
                                ]);
                            }
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
