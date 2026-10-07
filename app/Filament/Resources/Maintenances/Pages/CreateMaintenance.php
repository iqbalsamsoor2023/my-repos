<?php

namespace App\Filament\Resources\Maintenances\Pages;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenance extends CreateRecord
{
    protected static string $resource = MaintenanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['private_claim_item_id'] ?? null) === 'others') {
            $data['private_claim_item_id'] = null;
        }
    
        $data['reported_by'] = Auth()->user()->id;

        return $data;
    }
}
