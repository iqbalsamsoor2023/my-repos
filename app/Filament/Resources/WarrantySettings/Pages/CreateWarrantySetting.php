<?php

namespace App\Filament\Resources\WarrantySettings\Pages;

use App\Filament\Resources\WarrantySettings\WarrantySettingResource;
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
