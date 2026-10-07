<?php

namespace App\Filament\Resources\VisitorSettings\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitorSettings\VisitorSettingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitorSettings extends ListRecords
{
    protected static string $resource = VisitorSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
