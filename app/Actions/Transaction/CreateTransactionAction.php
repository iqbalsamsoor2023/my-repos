<?php

namespace App\Actions\Transaction;

use App\Exceptions\GeneralException;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class CreateTransactionAction
{
    public function execute(StoreTransactionRequest $request)
    {
        $transaction = Transaction::create($request->only([
            'invoice_id',
            'payment_id',
            'paid_amount',
            'transaction_datetime',
            'status',
            'payer_name',
            'bill_payee_bank_detail_id',
        ]));

        if (! $transaction) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating transaction');
        }

        $this->uploadReceiptImage($transaction, $request);

        return $transaction;
    }

    private function uploadReceiptImage(Transaction $transaction, $request)
    {
        if ($request->hasFile('slip_image')) {
            $transaction->addMediaFromRequest('slip_image')->withCustomProperties(['type' => 'bill-reminder-slip'])->toMediaCollection();
        }
    }
}
