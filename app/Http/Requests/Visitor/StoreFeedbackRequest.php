<?php

namespace App\Http\Requests\Visitor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visitor_log_id' => 'required|integer|exists:visitor_logs,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'feedback_remark' => 'required|string|max:191',
            'estamp_by_type' => 'nullable|integer',
            'estamp_by' => 'nullable|integer',
        ];
    }
}
