<?php

namespace App\Filament\Resources\PrivateClaimSuppliers\Pages;

use App\Filament\Resources\PrivateClaimSuppliers\PrivateClaimSupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrivateClaimSupplier extends CreateRecord
{
    protected static string $resource = PrivateClaimSupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // get residence IDs for this user
        $data['residence_id'] = get_residence_by_property_management($user->id);

        return $data;
    }
}
