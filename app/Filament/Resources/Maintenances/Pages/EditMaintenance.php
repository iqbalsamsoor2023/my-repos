<?php

namespace App\Filament\Resources\Maintenances\Pages;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use Filament\Resources\Pages\EditRecord;

class EditMaintenance extends EditRecord
{
    protected static string $resource = MaintenanceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['private_claim_item_id'] ?? null) === 'others') {
            $data['private_claim_item_id'] = null;
        }

        return $data;
    }
}
