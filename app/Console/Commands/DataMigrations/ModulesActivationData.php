<?php

namespace App\Console\Commands\DataMigrations;

use App\Enums\ActivationModule\ModuleType;
use App\Enums\GeneralStatus;
use App\Enums\Residence\Features;
use App\Models\ActivationModule;
use App\Models\ResidenceFeature;
use Exception;
use Illuminate\Support\Facades\DB;

class ModulesActivationData
{
    public function execute($residence_id)
    {
        try {
            DB::beginTransaction();
            $mmb1Data = DB::connection('mmb1')
                ->table('function_statuses')
                ->orderBy('function_statuses.id')
                ->where('function_statuses.residence_id', $residence_id)
                ->chunk(1000, function ($datas) {
                    foreach ($datas as $value) {
                        if ($value->inbox_status == true) {
                            $module_type = ModuleType::INBOX->value;
                            $feature_id = Features::INBOX->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::INBOX->value;
                            $feature_id = Features::INBOX->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->visitor_status == true) {
                            $module_type = ModuleType::VISITOR->value;
                            $feature_id = Features::VISITOR->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::VISITOR->value;
                            $feature_id = Features::VISITOR->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->claim_status == true) {
                            $module_type = ModuleType::CLAIM->value;
                            $feature_id = Features::CLAIM->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::CLAIM->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $feature_id = Features::CLAIM->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->parcel_status == true) {
                            $module_type = ModuleType::PARCEL->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $feature_id = Features::PARCEL->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::PARCEL->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $feature_id = Features::PARCEL->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->booking_status == true) {
                            $module_type = ModuleType::BOOKING->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $feature_id = Features::BOOKING->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::BOOKING->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $feature_id = Features::BOOKING->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->developer_status == true) {
                            $module_type = ModuleType::DEVELOPER->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $feature_id = Features::DEVELOPER->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::DEVELOPER->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $feature_id = Features::DEVELOPER->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->contact_status == true) {
                            $module_type = ModuleType::CONTACT->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $feature_id = Features::CONTACT->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::CONTACT->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $feature_id = Features::CONTACT->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        }

                        if ($value->billing_status == true) {
                            $module_type = ModuleType::BILLING->value;
                            $is_active = GeneralStatus::ACTIVE->value;
                            $feature_id = Features::BILLING->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
                        } else {
                            $module_type = ModuleType::BILLING->value;
                            $is_active = GeneralStatus::INACTIVE->value;
                            $feature_id = Features::BILLING->value;
                            $this->createActivationModule($value, $module_type, $is_active);
                            $this->createResidenceFeature($value, $feature_id, $is_active);
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

    public function createActivationModule($value, $module_type, $is_active)
    {
        ActivationModule::create([
            'residence_id' => $value->residence_id,
            'module' => 'residence',
            'module_type' => $module_type,
            'is_active' => $is_active,
            'created_at' => $value->created_at ?? now(),
            'updated_at' => $value->updated_at ?? null,
        ]);
    }

    public function createResidenceFeature($value, $feature_id, $is_active)
    {
        ResidenceFeature::create([
            'residence_id' => $value->residence_id,
            'feature_id' => $feature_id,
            'is_active' => $is_active,
        ]);
    }
}
