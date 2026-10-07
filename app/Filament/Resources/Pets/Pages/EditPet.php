<?php

namespace App\Filament\Resources\Pets\Pages;

use App\Filament\Resources\Pets\PetResource;
use Filament\Resources\Pages\EditRecord;

class EditPet extends EditRecord
{
    protected static string $resource = PetResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_pet');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['unit_id'] = $this->record->unit_id;
        $data['residence_id'] = $this->record->unit->residence_id;
        $data['type'] = $this->record->getAttributes()['type'];

        return $data;
    }
}
