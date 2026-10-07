<?php

namespace App\Http\Requests\User;

use App\Enums\User\Gender;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'country_id' => 'nullable|integer|exists:countries,id',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|unique:users,email|email:rfc,dns',
            'id_number' => 'nullable|string|max:20',
            'phone_no' => 'nullable|numeric',
            'date_of_birth' => 'nullable|date|before:tomorrow',
            'gender' => 'nullable|integer|in:'.Gender::MALE->value.','.Gender::FEMALE->value,
            'passport_number' => 'nullable|string|max:255',
            'passport_expiry' => 'nullable|date',
            'pdpa_agreed_at' => 'nullable|date',
        ];
    }
}
