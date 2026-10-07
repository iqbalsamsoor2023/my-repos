<?php

namespace App\Filament\Resources\LivingSpaces\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LivingSpaces\LivingSpaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLivingSpaces extends ListRecords
{
    protected static string $resource = LivingSpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
