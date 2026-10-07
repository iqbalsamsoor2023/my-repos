<?php

namespace App\Http\Requests\EmergencyContact;

use App\Enums\EmergencyContact\CoverageMode;
use App\Enums\GeneralStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyContactRequest extends FormRequest
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
            'department_type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:20',
            'coverage_mode' => 'required|integer|in:'.CoverageMode::NATIONWIDE->value.','.CoverageMode::PROVINCE->value,
            'is_active' => 'required|boolean|in:'.GeneralStatus::ACTIVE->value.','.GeneralStatus::INACTIVE->value,
        ];
    }
}
