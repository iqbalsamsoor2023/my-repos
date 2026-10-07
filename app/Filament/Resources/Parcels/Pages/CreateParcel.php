<?php

namespace App\Filament\Resources\Parcels\Pages;

use App\Actions\Parcel\CreateParcelAction;
use App\Filament\Resources\Parcels\ParcelResource;
use App\Models\Parcel;
use Filament\Resources\Pages\CreateRecord;

class CreateParcel extends CreateRecord
{
    protected static string $resource = ParcelResource::class;

    public function getTitle(): string
    {
        return __('menu.create_parcel');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

        return $data;
    }

    protected function afterCreate(): void
    {
        if (isset($this->record->receiver_id)) {
            $this->record->receiver_name = $this->record->receiver->name;
        }

        $this->toDatabase($this->record);
    }

    public function toDatabase(Parcel $parcel)
    {
        $parcel_action = new CreateParcelAction;

        return $parcel_action->sendParcelArrivedNotification($parcel);
    }
}
