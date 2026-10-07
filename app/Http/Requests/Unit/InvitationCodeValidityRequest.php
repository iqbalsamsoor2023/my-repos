<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

class InvitationCodeValidityRequest extends FormRequest
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
            'invitation_code' => 'required|numeric',
        ];
    }
}
