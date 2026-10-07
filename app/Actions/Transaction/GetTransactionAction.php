<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use Illuminate\Http\Request;

class GetTransactionAction
{
    public function execute(Request $request)
    {
        $transactions = Transaction::with('invoice', 'invoice.items', 'paymentMethod');

        if (isset($request->invoice_id)) {
            $transactions = $transactions->where('invoice_id', $request->invoice_id);
        }

        if (isset($request->status)) {
            $transactions = $transactions->where('status', $request->status);
        }

        if (isset($request->transaction_invoice_id)) {
            $transactions = $transactions->where('invoice_id', $request->transaction_invoice_id)
                ->whereHas('invoice', function ($query) use ($request) {
                    $query->where('id', $request->transaction_invoice_id);
                });
        }

        if (isset($request->transaction_status)) {
            $transactions = $transactions->where('status', $request->transaction_status)
                ->whereHas('invoice', function ($query) use ($request) {
                    $query->where('status', $request->transaction_status);
                });
        }

        return $transactions->orderBy('id', 'DESC')->get();
    }
}
