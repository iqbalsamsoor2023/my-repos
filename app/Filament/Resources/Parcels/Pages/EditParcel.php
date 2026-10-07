<?php

namespace App\Filament\Resources\Parcels\Pages;

use App\Enums\Parcel\ParcelStatus;
use App\Filament\Resources\Parcels\ParcelResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditParcel extends EditRecord
{
    protected static string $resource = ParcelResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_parcel');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($data['receiver_name'] == 'All') {
            $data['receiver_id'] = 'All';
        } elseif ($data['receiver_id'] == null && $data['receiver_name'] != 'All') {
            $data['receiver_id'] = 'Other';
        }

        $data['residence_id'] = $this->record->unit->residence_id;
        $data['receiver_id'] = $data['receiver_id'];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($data['receiver_id'] == 'All') {
            $data['receiver_id'] = null;
            $data['receiver_name'] = 'All';
        } elseif ($data['receiver_id'] == 'Other') {
            $data['receiver_id'] = null;
            $data['receiver_name'] = $data['receiver_name'];
        }

        $data['receiver_id'] = $data['receiver_id'];
        $data['receiver_name'] = isset($data['receiver_name']) ? $data['receiver_name'] : null;
        $data['updated_by_mmb_user_id'] = auth()->user()->id;

        $record->update($data);

        if (isset($record->receiver_id)) {
            $record->receiver_name = $record->receiver->name;
        }

        if ($record->status == ParcelStatus::PICKED_UP->value) {
            $record->pickup_time = now()->toDateTimeString();
        }

        $record->save();

        return $record;
    }
}
