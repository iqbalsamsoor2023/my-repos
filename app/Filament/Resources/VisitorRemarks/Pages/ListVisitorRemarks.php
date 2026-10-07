<?php

namespace App\Filament\Resources\VisitorRemarks\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorRemarks\VisitorRemarkResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitorRemarks extends ListRecords
{
    protected static string $resource = VisitorRemarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
