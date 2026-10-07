<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRentPropertyRequest extends FormRequest
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
            'rent_price' => 'required|integer',
            'rental_start_date' => 'required|date',
            'contract_months' => 'required|integer',
            'deposit' => 'required|integer',
            'has_custom_rule' => 'required|boolean',
            'custom_rules' => 'required_if:has_custom_rule,true',
            'is_active' => 'required|boolean',
        ];
    }
}
