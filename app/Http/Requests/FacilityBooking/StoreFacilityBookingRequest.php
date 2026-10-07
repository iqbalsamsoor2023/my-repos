<?php

namespace App\Http\Requests\FacilityBooking;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityBookingRequest extends FormRequest
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
            'facility_id' => 'required|integer|exists:residence_amenity,id',
            'user_id' => 'required|integer|exists:users,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'ref_no' => 'nullable|string|max:20',
            'start_at' => 'required|date|after_or_equal:today',
            'end_at' => 'required|date|after:start_date',
            'created_by' => 'required|integer|exists:users,id',
        ];
    }
}
