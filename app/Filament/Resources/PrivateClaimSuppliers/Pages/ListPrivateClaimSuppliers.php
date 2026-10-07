<?php

namespace App\Filament\Resources\PrivateClaimSuppliers\Pages;

use App\Filament\Resources\PrivateClaimSuppliers\PrivateClaimSupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrivateClaimSuppliers extends ListRecords
{
    protected static string $resource = PrivateClaimSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
