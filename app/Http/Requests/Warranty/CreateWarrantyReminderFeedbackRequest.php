<?php

namespace App\Http\Requests\Warranty;

use Illuminate\Foundation\Http\FormRequest;

class CreateWarrantyReminderFeedbackRequest extends FormRequest
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
            'unit_id' => 'required|integer|exists:units,id',
            'user_id' => 'required|integer|exists:users,id',
            'amenity_id' => 'required|integer|exists:amenities,id',
            'stop_remind_at' => 'required',
        ];
    }
}
