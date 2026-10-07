<?php

namespace App\Filament\Resources\WarrantySettingResource\Pages;

use App\Filament\Resources\WarrantySettingResource\WarrantySettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWarrantySetting extends CreateRecord
{
    protected static string $resource = WarrantySettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);
            $data['residence_id'] = $residence->id;
        }

        return $data;
    }
}
