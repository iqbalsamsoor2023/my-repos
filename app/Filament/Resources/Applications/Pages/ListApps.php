<?php

namespace App\Filament\Resources\Applications\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListApps extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Create New App')),
        ];
    }
}
