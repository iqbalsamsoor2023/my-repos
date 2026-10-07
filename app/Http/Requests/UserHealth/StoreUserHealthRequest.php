<?php

namespace App\Http\Requests\UserHealth;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserHealthRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'blood_type' => 'nullable|string|in:O-,O+,A+,A-,B+,B-,AB-,AB+|max:5',
            'height' => 'nullable',
            'weight' => 'nullable',
            'health_questionnaire_answers' => 'required|json',
            'insurance_company_id' => 'nullable|exists:App\Models\InsuranceCompany,id',
        ];
    }
}
