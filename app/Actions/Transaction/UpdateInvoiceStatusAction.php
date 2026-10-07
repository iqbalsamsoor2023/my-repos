<?php

namespace App\Actions\Transaction;

use App\Enums\Bill\BillStatus;
use App\Exceptions\GeneralException;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class UpdateInvoiceStatusAction
{
    public function execute(Invoice $invoice)
    {
        $invoice->update([
            'status' => BillStatus::PENDING->value,
        ]);

        if (! $invoice) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating invoice');
        }

        foreach ($invoice->items as $item) {
            $item->update([
                'status' => BillStatus::PENDING->value,
            ]);

            if (! $item) {
                throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating invoice item');
            }
        }

        return $invoice;
    }
}
