<?php

namespace App\Filament\Resources\BillTransactions\Pages;

use App\Filament\Resources\BillTransactions\BillTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListBillTransactions extends ListRecords
{
    protected static string $resource = BillTransactionResource::class;
}
