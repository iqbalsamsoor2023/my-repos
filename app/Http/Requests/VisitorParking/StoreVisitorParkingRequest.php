<?php

namespace App\Http\Requests\VisitorParking;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitorParkingRequest extends FormRequest
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
            'visitor_log_id' => 'required|integer|exists:visitor_logs,id',
            'amount_paid' => 'required|numeric',
            'discount_value' => 'nullable|numeric',
            'is_penalty' => 'required|boolean',
            'is_stamp' => 'required|boolean',
            'vehicle_type' => 'nullable|numeric',
            'voucher_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',

        ];
    }
}
