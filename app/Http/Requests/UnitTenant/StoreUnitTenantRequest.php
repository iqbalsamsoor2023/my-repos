<?php

namespace App\Http\Requests\UnitTenant;

use App\Enums\User\Gender;
use App\Enums\UserFamily\Relationship;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitTenantRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users,email|email:rfc,dns',
            'phone_no' => 'required|numeric',
            'date_of_birth' => 'sometimes|date|before:tomorrow',
            'gender' => 'nullable|integer|in:'.Gender::MALE->value.','.Gender::FEMALE->value,
            'relationship' => 'sometimes|integer|in:'.Relationship::HUSBAND->value.','.Relationship::WIFE->value.','
                                                    .Relationship::FATHER->value.','.Relationship::MOTHER->value.','
                                                    .Relationship::BROTHER->value.','.Relationship::SISTER->value.','
                                                    .Relationship::SON->value.','.Relationship::DAUGHTER->value.','
                                                    .Relationship::RELATIVE->value.','.Relationship::CO_HOME->value,
            'country_id' => 'nullable|integer|exists:countries,id',
            'id_number' => 'sometimes_if:country_id,1',
            'passport_number' => 'nullable',
            'passport_expiry' => 'nullable|date',
            'unit_id' => 'required|integer|exists:units,id',
            'role' => 'required|string',
        ];
    }
}
