<?php

namespace App\Filament\Resources\Couriers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Couriers\CourierResource;
use Filament\Resources\Pages\EditRecord;

class EditCourier extends EditRecord
{
    protected static string $resource = CourierResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_courier');
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
}
