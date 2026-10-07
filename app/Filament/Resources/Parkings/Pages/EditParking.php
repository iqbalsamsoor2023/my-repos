<?php

namespace App\Filament\Resources\Parkings\Pages;

use App\Enums\ActivationModule\ModuleType;
use App\Enums\Parking\ParkingType;
use App\Filament\Resources\Parkings\ParkingResource;
use App\Models\ActivationModule;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditParking extends EditRecord
{
    protected static string $resource = ParkingResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_parking');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->record->residence->activationModules as $activationModule) {
            if ($activationModule->module_type == ModuleType::PARKING_FEE->value) {
                $data['activationModules']['parkingFee'] = $activationModule->is_active;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->type == ParkingType::PAID->value) {
            $activationModule = ActivationModule::where('residence_id', $this->record->residence_id)->where('module_type', ModuleType::PARKING->value)->first();
            if ($activationModule) {
                $activationModule->update([
                    'is_active' => true,
                ]);
            } else {
                ActivationModule::create([
                    'residence_id' => $this->record->residence_id,
                    'module' => 'visitor',
                    'module_type' => ModuleType::PARKING->value,
                    'is_active' => true,
                ]);
            }
        }

        foreach ($this->data['activationModules'] as $key => $activationModule) {
            $this->checkActivationModule($activationModule, $key, $this->record->residence_id);
        }
    }

    private function checkActivationModule($is_active, $module_type, $residence_id)
    {
        if ($module_type == Str::camel(strtolower(ModuleType::PARKING_FEE->name))) {
            $type = ModuleType::PARKING_FEE->value;
            $this->updateActivationModule($residence_id, $type, $is_active);
        }
    }

    private function updateActivationModule($residence_id, $module_type, $is_active)
    {
        $activationModule = ActivationModule::where('residence_id', $residence_id)->where('module_type', $module_type)->first();
        $activationModule->update(['is_active' => $is_active]);
    }
}
