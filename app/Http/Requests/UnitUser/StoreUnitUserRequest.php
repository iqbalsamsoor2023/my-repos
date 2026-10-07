<?php

namespace App\Http\Requests\UnitUser;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitUserRequest extends FormRequest
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
            'unit_id' => 'required|integer|exists:units,id',
            'user_id' => 'required|integer|exists:users,id',
            'insurance_company_id' => 'nullable|integer|exists:insurance_companies,id',
            'is_main_owner' => 'nullable|integer',
            'is_main_tenant' => 'nullable|integer',
            'relationship' => 'nullable|integer',
        ];
    }
}
