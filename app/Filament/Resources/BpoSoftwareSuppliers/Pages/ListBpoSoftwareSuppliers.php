<?php

namespace App\Filament\Resources\BpoSoftwareSuppliers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BpoSoftwareSuppliers\BpoSoftwareSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBpoSoftwareSuppliers extends ListRecords
{
    protected static string $resource = BpoSoftwareSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
