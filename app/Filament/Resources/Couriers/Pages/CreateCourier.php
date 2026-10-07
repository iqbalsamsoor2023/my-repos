<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Enums\LogisticPartner\CategoryType;
use App\Filament\Resources\Couriers\CourierResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;

    public function getTitle(): string
    {
        return __('menu.create_courier');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['category'] = CategoryType::Courier->value;

        return static::getModel()::create($data);
    }
}
