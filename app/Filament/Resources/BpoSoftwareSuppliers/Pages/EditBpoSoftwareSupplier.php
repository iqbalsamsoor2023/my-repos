<?php

namespace App\Filament\Resources\BpoSoftwareSuppliers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BpoSoftwareSuppliers\BpoSoftwareSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBpoSoftwareSupplier extends EditRecord
{
    protected static string $resource = BpoSoftwareSupplierResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
