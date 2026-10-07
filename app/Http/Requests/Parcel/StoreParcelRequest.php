<?php

namespace App\Http\Requests\Parcel;

use Illuminate\Foundation\Http\FormRequest;

class StoreParcelRequest extends FormRequest
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
            'role_type' => 'required|integer|in:1,2',
            'unit_id' => 'required|integer|exists:units,id',
            'receiver_id' => 'nullable|integer|exists:users,id',
            'receiver_name' => 'required|string|max:255',
            'courier_id' => 'required_without:other_courier|integer|exists:logistic_partners,id',
            'other_courier' => 'required_without:courier_id|nullable|string',
            'tracking_no' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:3000',
            'images' => 'required|array',
            'images.*' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'created_by' => 'required|integer',
        ];
    }
}
