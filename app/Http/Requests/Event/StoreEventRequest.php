<?php

namespace App\Http\Requests\Event;

use App\Enums\GeneralStatus;
use App\Enums\GeneralSwitch;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'residence_id' => 'required|integer|exists:residences,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'start_at' => 'required|after_or_equal:today',
            'end_at' => 'required|after_or_equal:start_at',
            'is_active' => 'required|integer|in:'.GeneralStatus::ACTIVE->value.','.GeneralStatus::INACTIVE->value,
            'is_cancel' => 'required|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
            'created_by' => 'required|integer|exists:users,id',
            'updated_by' => 'nullable|integer|exists:users,id',
            'image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
        ];
    }
}
