<?php

namespace App\Http\Requests\User;

use stdClass;
use Illuminate\Validation\ValidationException;
use App\Enums\Unit\InvitationType;
use App\Enums\User\Gender;
use App\Enums\User\RoleType;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StoreUserRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'country_id' => 'nullable|integer|exists:countries,id',
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($request) {
                    $user = User::where('email', $request->email)->withTrashed()->first();

                    if ($user) {
                        if ((is_null($user->email_verified_at) == false) && (is_null($user->deleted_at) == true)) {
                            return $fail('The '.$attribute.' address already registered and verified.');
                        }

                        if (empty($user->email_verified_at == true) && empty($user->deleted_at == true)) {
                            return $fail('The '.$attribute.' address already registered but not yet verify.');
                        }

                        if (is_null($user->deleted_at) == false) {
                            return $fail('The '.$attribute.' address already registered but has been deleted.');
                        }
                    }
                },
            ],
            // 'email' => 'required|string|unique:users,email|email:rfc,dns',
            'email_verfied_at' => 'nullable|date',
            'id_number' => 'nullable|string|max:20',
            'password' => 'required|min:8',
            'phone_no' => 'required|numeric',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date|before:tomorrow',
            'gender' => 'nullable|integer|in:'.Gender::MALE->value.','.Gender::FEMALE->value,
            'passport_number' => 'nullable|string|max:255',
            'passport_expiry' => 'nullable|date',
            'image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'invitation_type' => 'nullable|integer|in:'.InvitationType::OWNER->value.','.InvitationType::TENANT->value,
            'unit_id' => 'required|integer|exists:units,id',
            'residence_id' => 'required|integer|exists:residences,id',
            'role' => 'string|required_without:invitation_type|in:'.RoleType::UNIT_OWNER->value.','.(RoleType::UNIT_TENANT->value),
            'pdpa_agreed_at' => 'nullable|date',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = error($validator->errors()->first(), new stdClass, 422);

        throw new ValidationException($validator, $response);
    }
}
