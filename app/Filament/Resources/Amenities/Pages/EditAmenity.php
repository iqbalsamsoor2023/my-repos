<?php

namespace App\Filament\Resources\Amenities\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Amenities\AmenityResource;
use App\Filament\Resources\Warranties\WarrantyResource;
use Filament\Resources\Pages\EditRecord;

class EditAmenity extends EditRecord
{
    protected static string $resource = AmenityResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_amenity');
    }

    protected function getRedirectUrl(): string
    {
        $recordId = $this->record->residence_id;

        return WarrantyResource::getUrl('edit', ['record' => $recordId]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['has_warranty'] == false) {
            $data['warranty_period'] = 0;
            $data['period_type'] = null;
            $data['supplier'] = null;
            $data['is_out_warranty'] = 0;
        }

        return $data;
    }
}
