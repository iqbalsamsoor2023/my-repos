<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'device_id' => 'required|string',
            'fcm_token' => 'nullable|string',
            'huawei_token' => 'nullable|string',
            'package_name' => 'nullable|string|max:100', // temporary nullable for staging
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:255',
        ];
    }
}
