<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;

class GetOneInvoiceAction
{
    public function execute(int $id): ?Model
    {
        $invoice = Invoice::with(
            'unit',
            'unit.residence',
            'unit.residence.subdistrict',
            'unit.residence.subdistrict.district',
            'unit.residence.subdistrict.district.province',
            'payers',
            'payers.user',
            'items',
            'items.histories',
            'billPayeeSetting',
            'billPayeeSetting.accounts',
            'billPayeeSetting.accounts.bank',
            'billPayeeSetting.residence.propertyManagementUser'
        )
            ->findOrFail($id);

        return $invoice;
    }
}
