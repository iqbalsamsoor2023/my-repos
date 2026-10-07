<?php

namespace App\Filament\Resources\LivingSpaces\Pages;

use App\Filament\Resources\LivingSpaces\LivingSpaceResource;
use App\Enums\HouseholdItem\ItemCategoryEnum;
use Filament\Resources\Pages\CreateRecord;

class CreateLivingSpace extends CreateRecord
{
    protected static string $resource = LivingSpaceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->setCategory($data);
    }

    protected function setCategory(array $data): array
    {
        $data['category'] = ItemCategoryEnum::SPACE->value;

        return $data;
    }
}
