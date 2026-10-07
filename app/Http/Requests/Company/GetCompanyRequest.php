<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class GetCompanyRequest extends FormRequest
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
            'type' => 'nullable|string|in:Developer,Property Management,Residence,Fire Insurance,Vehicle Insurance',
        ];
    }
}
