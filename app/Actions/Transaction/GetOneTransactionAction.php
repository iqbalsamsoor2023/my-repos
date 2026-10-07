<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use Illuminate\Http\Request;

class GetOneTransactionAction
{
    public function execute(Request $request, int $id)
    {
        $transaction = Transaction::with(
            'invoice',
            'invoice.items',
            'invoice.unit',
            'invoice.unit.residence',
            'invoice.billPayeeSetting',
            'invoice.billPayeeSetting.accounts',
            'invoice.billPayeeSetting.accounts.bank',
            'invoice.billPayeeSetting.residence.propertyManagementUser',
            'paymentMethod',
            'bankAccountDetail',
            'bankAccountDetail.bank'
        );

        if (isset($request->status)) {
            $transaction = $transaction->where('status', $request->status);
        }

        return $transaction->findOrFail($id);
    }
}
