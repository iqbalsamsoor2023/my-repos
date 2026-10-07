<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUnitRequest extends FormRequest
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
            'residence_id' => 'nullable|integer|exists:residences,id',
            'name' => 'nullable|string',
            'unit_number' => 'nullable|string',
        ];
    }
}
