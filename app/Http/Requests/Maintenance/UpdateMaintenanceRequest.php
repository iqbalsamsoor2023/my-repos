<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\Maintenance\MaintenanceStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
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
            'status' => 'nullable|integer|between:'.MaintenanceStatus::IN_PROGRESS->value.','.MaintenanceStatus::COMPLETE->value,
            'progress_image' => 'nullable|mimes:jpeg,png,jpg,pdf',
            'progress_description' => 'nullable|string|max:255',
            'completed_remark' => 'nullable|string|max:255',
            'is_verified' => 'nullable|integer|between:0,1',
            'verification_description' => 'nullable|string|max:255',
            'rating' => 'nullable|integer|between:1,5',
            'verification_image' => 'nullable|mimes:jpeg,png,jpg,pdf',
            'completion_datetime' => 'sometimes|date',
        ];
    }
}
