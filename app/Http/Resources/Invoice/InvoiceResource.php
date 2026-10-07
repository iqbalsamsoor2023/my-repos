<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'bill_no' => $this->bill_no,
            'bill_date' => $this->bill_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'total_amount' => $this->total_amount,
            'amount_due' => $this->amount_due,
            'remark' => $this->remark,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'status' => $item->status,
                ];
            }),
            'transactions' => $this->transactions->map(function ($transaction) {
                return [
                    'ref_no' => $transaction->ref_no,
                ];
            }),
            'billPayeeSetting' => [
                'bank_qr_code_url' => $this->billPayeeSetting->image_url,
                'accounts' => $this->billPayeeSetting->accounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'bank_id' => $account->bank_id,
                        'name' => $account?->bank->name,
                        'bank_name' => app()->getLocale() === 'th' ? $account?->bank->name_th : $account?->bank->name,
                        'payee_account_number' => $account->payee_account_number,
                        'payee_account_name' => $account->payee_account_name,
                        'payee_account_name_th' => $account->payee_account_name_th,
                    ];
                }),
            ],
        ];
    }
}
