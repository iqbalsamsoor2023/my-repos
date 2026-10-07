<?php

namespace App\Filament\Resources\BillTransactions\Pages;

use App\Filament\Resources\BillTransactions\BillTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillTransaction extends CreateRecord
{
    protected static string $resource = BillTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
