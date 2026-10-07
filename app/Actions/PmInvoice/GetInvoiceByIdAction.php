<?php

namespace App\Actions\PmInvoice;

use App\Models\PmInvoice;
use Illuminate\Database\Eloquent\Model;

class GetInvoiceByIdAction
{
    public function execute(int $id): ?Model
    {
        $invoice = PmInvoice::with(
        'residence',
            'unit',
            'issuedUser',
            'billings.invoiceType',
            'billings.residence',
            'outstandingInvoices'
            )->findOrFail($id);

        return $invoice;
    }
}
