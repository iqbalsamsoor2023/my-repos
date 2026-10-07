<?php

namespace App\Filament\Resources\VisitorPurposes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorPurposes\VisitorPurposeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitorPurposes extends ListRecords
{
    protected static string $resource = VisitorPurposeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
