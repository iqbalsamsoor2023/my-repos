<?php

namespace App\Filament\Resources\BpoSoftwareSuppliers\Pages;

use App\Filament\Resources\BpoSoftwareSuppliers\BpoSoftwareSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBpoSoftwareSupplier extends CreateRecord
{
    protected static string $resource = BpoSoftwareSupplierResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
