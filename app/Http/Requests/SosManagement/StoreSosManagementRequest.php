<?php

namespace App\Http\Requests\SosManagement;

use App\Enums\SosManagement\UserActionRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreSosManagementRequest extends FormRequest
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
            'created_by_id' => 'required|integer|exists:users,id',
            'unit_id' => 'required|integer|exists:units,id',
            'longitude' => 'required|string',
            'latitude' => 'required|string',
            'user_action_request' => 'nullable|integer|in:'.UserActionRequest::CALL_AMBULANCE->value.','.UserActionRequest::CALL_POLICE->value,
        ];
    }
}
