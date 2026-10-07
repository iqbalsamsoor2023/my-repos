<?php

namespace App\Filament\Resources\Furnitures\Pages;

use App\Filament\Resources\Furnitures\FurnitureResource;
use App\Enums\HouseholdItem\ItemCategoryEnum;
use Filament\Resources\Pages\CreateRecord;

class CreateFurniture extends CreateRecord
{
    protected static string $resource = FurnitureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->setCategory($data);
    }

    protected function setCategory(array $data): array
    {
        $data['category'] = ItemCategoryEnum::FURNITURE->value;

        return $data;
    }
}
