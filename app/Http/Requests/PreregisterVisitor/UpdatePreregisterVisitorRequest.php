<?php

namespace App\Http\Requests\PreregisterVisitor;

use App\Enums\GeneralSwitch;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreregisterVisitorRequest extends FormRequest
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
            'is_qr_code_expired' => 'nullable|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
        ];
    }
}
