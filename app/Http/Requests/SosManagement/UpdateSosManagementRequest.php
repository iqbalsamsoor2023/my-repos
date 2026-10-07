<?php

namespace App\Http\Requests\SosManagement;

use App\Enums\SosManagement\Status;
use App\Enums\SosManagement\UserActionRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSosManagementRequest extends FormRequest
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
            'accepted_by_id' => 'nullable|integer',
            'user_action_request' => 'nullable|integer|in:'.UserActionRequest::CALL_AMBULANCE->value.','.UserActionRequest::CALL_POLICE->value,
            'status' => 'nullable|integer|in:'.Status::PENDING->value.','.Status::IN_PROGRESS->value.','.Status::CANCELLED->value.','.Status::COMPLETED->value,
            'remark' => 'nullable|string',
        ];
    }
}
