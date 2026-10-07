<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\SalesAndTenancies\BankLoanStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'residence_id' => 'required',
            'unit_id' => 'required',
            'sale_price' => 'required|integer',
            'have_ownership_documents' => 'required|boolean',
            'bank_loan_status' => ['required', Rule::enum(BankLoanStatusEnum::class)],
            'is_active' => 'required|boolean',
        ];
    }
}
