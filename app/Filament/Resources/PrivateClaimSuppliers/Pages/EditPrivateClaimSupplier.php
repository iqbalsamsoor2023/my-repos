<?php

namespace App\Filament\Resources\PrivateClaimSuppliers\Pages;

use App\Filament\Resources\PrivateClaimSuppliers\PrivateClaimSupplierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrivateClaimSupplier extends EditRecord
{
    protected static string $resource = PrivateClaimSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
