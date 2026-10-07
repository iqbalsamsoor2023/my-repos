<?php

namespace App\Http\Requests\VisitingArrangement;

use Illuminate\Foundation\Http\FormRequest;

class GetVisitingArrangementRequest extends FormRequest
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
            'visitor_log_id' => 'integer|exists:visitor_logs,id',
            'unit_id' => 'integer|exists:units,id',
            'user_id' => 'integer|exists:users,id',
        ];
    }
}
