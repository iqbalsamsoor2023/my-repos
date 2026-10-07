<?php

namespace App\Filament\Resources\SaleManagements\Pages;

use App\Filament\Resources\SaleManagements\SaleManagementResource;
use App\Models\Unit;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSaleManagement extends CreateRecord
{
    protected static string $resource = SaleManagementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $unit = Unit::find($data['unit_id']);
        $unit->update([
            'construction_progress' => $data['construction_progress'],
        ]);

        $data['residence_id'] = $unit->residence_id;

        unset($data['construction_progress']);

        $modelData = static::getModel()::create($data);

        return $modelData;
    }
}
