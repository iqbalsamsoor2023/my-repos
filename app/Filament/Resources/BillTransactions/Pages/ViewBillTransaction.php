<?php

namespace App\Filament\Resources\BillTransactions\Pages;

use App\Filament\Resources\BillTransactions\BillTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBillTransaction extends ViewRecord
{
    protected static string $resource = BillTransactionResource::class;

    public function getTitle(): string
    {
        return __('menu.view_bill_reminder_slip');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['transaction_time'] = $data['transaction_datetime'];

        return $data;
    }
}
