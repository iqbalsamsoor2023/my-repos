<?php

namespace App\Http\Requests\Visitor;

use Illuminate\Foundation\Http\FormRequest;

class GetVisitorRequest extends FormRequest
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
            'id' => 'nullable|integer|exists:visitor_logs,id',
            'visitor_id' => 'nullable|integer|exists:visitors,id',
            'vehicle_plate_no' => 'nullable|string',
            'visitor_code' => 'nullable|string',
            'residence_id' => 'nullable|integer|exists:residences,id',
            'name_vehicle_no' => 'nullable|string',
            'name' => 'nullable|string',
            'status' => 'nullable|in:0,1,2',
            'item_per_page' => 'nullable|integer',
        ];
    }
}
