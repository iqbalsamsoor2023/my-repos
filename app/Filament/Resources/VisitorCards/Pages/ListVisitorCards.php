<?php

namespace App\Filament\Resources\VisitorCards\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorCards\VisitorCardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitorCards extends ListRecords
{
    protected static string $resource = VisitorCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
