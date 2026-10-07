<?php

namespace App\Filament\Resources\BillPayeeSettings\Pages;

use App\Filament\Resources\BillPayeeSettings\BillPayeeSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillPayeeSetting extends CreateRecord
{
    protected static string $resource = BillPayeeSettingResource::class;

    public function getTitle(): string
    {
        return __('menu.create_bill_setting');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
