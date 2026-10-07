<?php

namespace App\Filament\Resources\HomeAppliances\Pages;

use App\Filament\Resources\HomeAppliances\HomeApplianceResource;
use App\Enums\HouseholdItem\ItemCategoryEnum;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeAppliance extends CreateRecord
{
    protected static string $resource = HomeApplianceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->setCategory($data);
    }

    protected function setCategory(array $data): array
    {
        $data['category'] = ItemCategoryEnum::HOME_APPLIANCE->value;

        return $data;
    }
}
