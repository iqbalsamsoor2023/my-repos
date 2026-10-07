<?php

namespace App\Filament\Resources\FaqTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\FaqTypes\FaqTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqTypes extends ListRecords
{
    protected static string $resource = FaqTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
