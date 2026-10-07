<?php

namespace App\Filament\Resources\Furnitures\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Furnitures\FurnitureResource;
use Filament\Resources\Pages\ListRecords;

class ListFurniture extends ListRecords
{
    protected static string $resource = FurnitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
