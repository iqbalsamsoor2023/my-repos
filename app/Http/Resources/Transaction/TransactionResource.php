<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $response = [
            'id' => $this->id,
            'ref_no' => $this->ref_no,
            'paid_amount' => $this->paid_amount,
            'invoice' => [
                'items' => $this->invoice?->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'vat' => $item->vat,
                        'status' => $item->status,
                    ];
                })->toArray() ?? [],
            ],
            'payment_method' => [
                'payment_mode' => optional($this->paymentMethod)->payment_mode,
            ],
        ];

        // to be removed after discontinuing v2 app support
        if (! $request->header('X-App-Version')) {
            $response['created_at'] = $this->invoice?->created_at;
            $response['updated_at'] = $this->invoice?->updated_at;
        }

        return $response;
    }
}
