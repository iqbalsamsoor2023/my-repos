<?php

namespace App\Http\Requests\VisitingArrangement;

use App\Enums\Visitor\EstampByType;
use App\Enums\Visitor\VisitingArrangementStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitingArrangementRequest extends FormRequest
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
            'status' => 'nullable|integer|in:'.VisitingArrangementStatus::MY_VISITOR->value.','.VisitingArrangementStatus::NOT_MY_VISITOR->value.','.
            VisitingArrangementStatus::CANCEL_BY_SG->value,
            'estamp_by' => 'nullable|integer',
            'estamp_by_type' => 'nullable|integer|in:'.EstampByType::RESIDENT->value.','.EstampByType::SG->value.','.EstampByType::PM->value.','.EstampByType::SGOC->value,
            'feedback_remark' => 'nullable|string|max:255',
            'unit_id' => 'nullable|integer|exists:units,id',
        ];
    }
}
