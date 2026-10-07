<?php

namespace App\Http\Requests\UserFamily;

use App\Enums\User\Gender;
use App\Enums\UserFamily\Relationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserFamilyRequest extends FormRequest
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
            // 'email' => 'required|string|max:255|unique:users,email|email:rfc,dns',
            'email' => [
                'required',
                'string',
                'max:255',
                'email:rfc,dns',
                Rule::when($this->input('created_via_family') == true, 'unique:users,email'), // created_via_family true means main owner/main tenant is adding new user
            ],
            'phone_no' => 'required|numeric',
            'date_of_birth' => 'required|date|before:tomorrow',
            'gender' => 'required|integer|in:'.Gender::MALE->value.','.Gender::FEMALE->value,
            'relationship' => 'required|integer|in:'.Relationship::HUSBAND->value.','.Relationship::WIFE->value.','
                                                    .Relationship::FATHER->value.','.Relationship::MOTHER->value.','
                                                    .Relationship::BROTHER->value.','.Relationship::SISTER->value.','
                                                    .Relationship::SON->value.','.Relationship::DAUGHTER->value.','
                                                    .Relationship::RELATIVE->value.','.Relationship::CO_HOME->value,
            'country_id' => 'required|integer|exists:countries,id',
            'id_number' => 'required_if:country_id,1',
            'passport_number' => 'required_unless:country_id,1',
            'passport_expiry' => 'required_unless:country_id,1',
            'unit_id' => 'required|integer|exists:units,id',
            'role' => 'required|string',
            'is_owner' => 'required|integer|in:1,0',
        ];
    }
}
