<?php

namespace App\Filament\Resources\BillPayeeSettings\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BillPayeeSettings\BillPayeeSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditBillPayeeSetting extends EditRecord
{
    protected static string $resource = BillPayeeSettingResource::class;

    public function getTitle(): string
    {
        return __('menu.edit_bill_setting');
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
