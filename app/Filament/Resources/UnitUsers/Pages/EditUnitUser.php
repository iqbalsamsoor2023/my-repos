<?php

namespace App\Filament\Resources\UnitUsers\Pages;

use Filament\Actions\DeleteAction;
use App\Enums\GeneralStatus;
use App\Filament\Resources\UnitUsers\UnitUserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUnitUser extends EditRecord
{
    protected static string $resource = UnitUserResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_resident');
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
        $data['residence_id'] = $this->record->unit?->residence_id ?? null;
        $data['relationship'] = $this->record->getAttributes()['relationship'] ?? null;
        $data['email_status'] = $this->record->user?->email_verified_at ? GeneralStatus::ACTIVE->value : GeneralStatus::INACTIVE->value;
        $data['user_id'] = $this->record->user?->id ?? null;
        $data['insurance_company_id'] = $this->record->user?->insurance_company_id ?? null;
        $data['insurance_policy_no'] = $this->record->user?->insurance_policy_no ?? null;

        return $data;
    }

    public function refreshFormData(array $attributes): void
    {
        unset($attributes['email_status']);

        parent::refreshFormData($attributes);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        if (array_key_exists('email_status', $data)) {
            $record->user->fill([
                'email_verified_at' => $data['email_status'] == GeneralStatus::ACTIVE->value ? $record->freshTimestamp() : null,
            ])->save();
        }

        return $record;
    }
}
