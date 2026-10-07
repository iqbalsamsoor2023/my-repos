<?php

namespace App\Filament\Resources\BlacklistVisitors\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BlacklistVisitors\BlacklistVisitorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBlacklistVisitor extends EditRecord
{
    protected static string $resource = BlacklistVisitorResource::class;

    public function getTitle(): string
    {
        return __('Edit Blacklist Visitor');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['name'] = $this->record->visitor->name;
        $data['id_type'] = $this->record->visitor->id_type;
        $data['id_number'] = $this->record->visitor->id_number;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->visitor->update($data);
        $record->update($data);

        return $record;
    }
}
