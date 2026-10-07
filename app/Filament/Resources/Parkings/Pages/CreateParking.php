<?php

namespace App\Filament\Resources\Parkings\Pages;

use App\Enums\ActivationModule\ModuleType;
use App\Enums\Parking\ParkingType;
use App\Filament\Resources\Parkings\ParkingResource;
use App\Models\ActivationModule;
use Filament\Resources\Pages\CreateRecord;

class CreateParking extends CreateRecord
{
    protected static string $resource = ParkingResource::class;

    public function getTitle(): string
    {
        return __('menu.create_parking');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
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
    }
}
