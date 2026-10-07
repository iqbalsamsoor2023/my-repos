<?php

namespace App\Http\Requests\Transaction;

use App\Enums\Bill\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'invoice_id' => 'required|integer|exists:invoices,id|bail',
            'paid_amount' => 'required|numeric|regex:/^\d*(\.\d{1,2})?$/|bail',
            'transaction_datetime' => 'required|date|before:tomorrow',
            'status' => 'nullable|integer|in:'.TransactionStatus::PENDING->value.','.TransactionStatus::ACCEPTED->value.
                ','.TransactionStatus::REJECTED->value,
            'payer_name' => 'required|string|max:255',
            'slip_image' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'bill_payee_bank_detail_id' => 'nullable|integer|exists:bill_payee_bank_details,id',
        ];
    }
}
