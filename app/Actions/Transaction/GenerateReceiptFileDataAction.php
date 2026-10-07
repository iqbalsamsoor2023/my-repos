<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;

class GenerateReceiptFileDataAction
{
    public function execute(Transaction $transaction)
    {
        $items = $transaction->invoice->items->where('status', '!=', 6); // 6 = CANCEL

        $residentNames = $transaction->invoice->payers
            ->map(function ($payer) {
                return $payer->user?->name ? ucfirst($payer->user->name) : null;
            })
            ->filter() // remove nulls
            ->implode(', ');

        $grandTotal = $items->sum('price');

        $data = [
            'transaction' => $transaction,
            'residentName' => $residentNames,
            'items' => $items,
            'grandTotal' => $grandTotal,
            'base64MooBanLogo' => $transaction->invoice->billPayeeSetting->residence->propertyManagementUser->image_base64 ?? null,
        ];

        return $data;
    }
}