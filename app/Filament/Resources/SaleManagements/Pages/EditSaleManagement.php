<?php

namespace App\Filament\Resources\SaleManagements\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SaleManagements\SaleManagementResource;
use App\Models\Unit;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSaleManagement extends EditRecord
{
    protected static string $resource = SaleManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $unit = Unit::where('id', $data['unit_id'])->first();
        $data['construction_progress'] = $unit ? $unit->construction_progress : null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! is_null($record->unit_id)) {
            Unit::where('id', $record->unit_id)->update([
                'construction_progress' => $data['construction_progress'],
            ]);
        }

        unset($data['construction_progress']);

        $record->update($data);

        return $record;
    }
}
