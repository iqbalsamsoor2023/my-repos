<?php

namespace App\Filament\Resources\Furnitures\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Furnitures\FurnitureResource;
use Filament\Resources\Pages\EditRecord;

class EditFurniture extends EditRecord
{
    protected static string $resource = FurnitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
