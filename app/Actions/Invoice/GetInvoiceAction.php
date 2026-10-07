<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class GetInvoiceAction
{
    public function execute($request)
    {
        $invoice = Invoice::with('unit', 'unit.residence', 'payers', 'items', 'items.histories', 'transactions', 'billPayeeSetting');

        if (isset($request->id)) {
            $invoice = $invoice->whereId($request->id);
        }

        if (isset($request->payer_unit_id)) {
            $invoice = $invoice->where('payer_unit_id', $request->payer_unit_id);
        }

        if (isset($request->payer_id)) {
            $invoice = $invoice->whereHas('payers', function ($query) use ($request) {
                return $query->where('payer_id', $request->payer_id);
            });
        }

        if (isset($request->status)) {
            $invoice = $invoice->where('status', $request->status);
        }

        return $invoice->orderBy('id', 'DESC')->paginate(20);
    }
}
