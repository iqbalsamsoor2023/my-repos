<?php

namespace App\Filament\Resources\Vms\Pages;

use App\Enums\ActivationModule\ModuleType;
use App\Filament\Resources\Vms\VmsResource;
use App\Models\ActivationModule;
use App\Models\VisitorSetting;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditVms extends EditRecord
{
    protected static string $resource = VmsResource::class;

    public function getTitle(): string
    {
        return __('Edit Visitor Management');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->record->activationModules as $activationModule) {
            if ($activationModule->module_type == ModuleType::BRAND->value) {
                $data['activationModules']['brand'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::PURPOSE_OF_VISIT->value) {
                $data['activationModules']['purposeOfVisit'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::CONTACT_NUMBER->value) {
                $data['activationModules']['contactNumber'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::TEMPERATURE->value) {
                $data['activationModules']['temperature'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::COMPANY_NAME->value) {
                $data['activationModules']['companyName'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::PASSENGER->value) {
                $data['activationModules']['passenger'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::REMARK->value) {
                $data['activationModules']['remark'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::COLOR->value) {
                $data['activationModules']['color'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::PDPA->value) {
                $data['activationModules']['pdpa'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VISITOR_PHOTO->value) {
                $data['activationModules']['visitorPhoto'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::SCAN_VISITOR_CARD->value) {
                $data['activationModules']['scanVisitorCard'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VEHICLE_PHOTO->value) {
                $data['activationModules']['vehiclePhoto'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::FOOD_AND_PARCEL->value) {
                $data['activationModules']['foodAndParcel'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::PARKING->value) {
                $data['activationModules']['parking'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_VISITOR_CARD->value) {
                $data['activationModules']['vsVisitorCard'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_VISITOR_NAME->value) {
                $data['activationModules']['vsVisitorName'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_VEHICLE_PLATE_NO->value) {
                $data['activationModules']['vsVehiclePlateNo'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_PROVINCE_OF_VEHICLE->value) {
                $data['activationModules']['vsProvinceOfVehicle'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_BRAND->value) {
                $data['activationModules']['vsBrand'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_COLOR->value) {
                $data['activationModules']['vsColor'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_PURPOSE_OF_VISIT->value) {
                $data['activationModules']['vsPurposeOfVisit'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_VEHICLE_TYPE->value) {
                $data['activationModules']['vsVehicleType'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_CONTACT_AT->value) {
                $data['activationModules']['vsContactAt'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_CONTACT_NUMBER->value) {
                $data['activationModules']['vsContactNumber'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_TEMPERATURE->value) {
                $data['activationModules']['vsTemperature'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_COMPANY_NAME->value) {
                $data['activationModules']['vsCompanyName'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_PASSENGER->value) {
                $data['activationModules']['vsPassenger'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_REMARK->value) {
                $data['activationModules']['vsRemark'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_STAMP->value) {
                $data['activationModules']['vsStamp'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_MOOBAN_LOGO->value) {
                $data['activationModules']['vsMoobanLogo'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_MOOBAN_LOGO->value) {
                $data['activationModules']['vsMoobanLogo'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_ONLY_SIGNATURE->value) {
                $data['activationModules']['vsOnlySignature'] = $activationModule->is_active;
            } elseif ($activationModule->module_type == ModuleType::VS_ONLY_STAMP->value) {
                $data['activationModules']['vsOnlyStamp'] = $activationModule->is_active;
            }
        }

        $data['is_qr_active'] = isset($this->record->visitorSetting) ? $this->record->visitorSetting->is_qr_active : false;

        return $data;
    }

    protected function afterSave(): void
    {
        foreach ($this->data['activationModules'] as $key => $activationModule) {
            $this->checkActivationModule($activationModule, $key, $this->record->id);
        }

        $visitor_setting = VisitorSetting::where('residence_id', $this->record->id)->first();

        if ($visitor_setting) {
            $visitor_setting->update(['is_qr_active' => $this->data['is_qr_active']]);
        } else {
            VisitorSetting::create([
                'residence_id' => $this->record->id,
                'is_qr_active' => $this->data['is_qr_active'],
            ]);
        }
    }

    private function checkActivationModule($is_active, $module_type, $residence_id)
    {
        if ($module_type == strtolower(ModuleType::BRAND->name)) {
            $type = ModuleType::BRAND->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::PURPOSE_OF_VISIT->name))) {
            $type = ModuleType::PURPOSE_OF_VISIT->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::CONTACT_NUMBER->name))) {
            $type = ModuleType::CONTACT_NUMBER->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == strtolower(ModuleType::TEMPERATURE->name)) {
            $type = ModuleType::TEMPERATURE->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::COMPANY_NAME->name))) {
            $type = ModuleType::COMPANY_NAME->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == strtolower(ModuleType::PASSENGER->name)) {
            $type = ModuleType::PASSENGER->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == strtolower(ModuleType::REMARK->name)) {
            $type = ModuleType::REMARK->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == strtolower(ModuleType::COLOR->name)) {
            $type = ModuleType::COLOR->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == strtolower(ModuleType::PDPA->name)) {
            $type = ModuleType::PDPA->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VISITOR_PHOTO->name))) {
            $type = ModuleType::VISITOR_PHOTO->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::SCAN_VISITOR_CARD->name))) {
            $type = ModuleType::SCAN_VISITOR_CARD->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VEHICLE_PHOTO->name))) {
            $type = ModuleType::VEHICLE_PHOTO->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::FOOD_AND_PARCEL->name))) {
            $type = ModuleType::FOOD_AND_PARCEL->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == strtolower(ModuleType::PARKING->name)) {
            $type = ModuleType::PARKING->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_VISITOR_CARD->name))) {
            $type = ModuleType::VS_VISITOR_CARD->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_VISITOR_NAME->name))) {
            $type = ModuleType::VS_VISITOR_NAME->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_VEHICLE_PLATE_NO->name))) {
            $type = ModuleType::VS_VEHICLE_PLATE_NO->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_PROVINCE_OF_VEHICLE->name))) {
            $type = ModuleType::VS_PROVINCE_OF_VEHICLE->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_BRAND->name))) {
            $type = ModuleType::VS_BRAND->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_COLOR->name))) {
            $type = ModuleType::VS_COLOR->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_PURPOSE_OF_VISIT->name))) {
            $type = ModuleType::VS_PURPOSE_OF_VISIT->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_VEHICLE_TYPE->name))) {
            $type = ModuleType::VS_VEHICLE_TYPE->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_CONTACT_AT->name))) {
            $type = ModuleType::VS_CONTACT_AT->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_CONTACT_NUMBER->name))) {
            $type = ModuleType::VS_CONTACT_NUMBER->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_TEMPERATURE->name))) {
            $type = ModuleType::VS_TEMPERATURE->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_COMPANY_NAME->name))) {
            $type = ModuleType::VS_COMPANY_NAME->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_PASSENGER->name))) {
            $type = ModuleType::VS_PASSENGER->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_REMARK->name))) {
            $type = ModuleType::VS_REMARK->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_STAMP->name))) {
            $type = ModuleType::VS_STAMP->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_MOOBAN_LOGO->name))) {
            $type = ModuleType::VS_MOOBAN_LOGO->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_ONLY_SIGNATURE->name))) {
            $type = ModuleType::VS_ONLY_SIGNATURE->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        } elseif ($module_type == Str::camel(strtolower(ModuleType::VS_ONLY_STAMP->name))) {
            $type = ModuleType::VS_ONLY_STAMP->value;
            $this->updateActivationModule($residence_id, $type, $is_active, 'visitor slip');
        }
    }

    private function updateActivationModule($residence_id, $module_type, $is_active, $module)
    {
        $activationModule = ActivationModule::where('residence_id', $residence_id)->where('module_type', $module_type)->first();

        if ($activationModule) {
            $activationModule->update(['is_active' => $is_active]);
        } else {
            ActivationModule::create([
                'residence_id' => $residence_id,
                'module' => $module,
                'module_type' => $module_type,
                'is_active' => $is_active,
            ]);
        }
    }
}
