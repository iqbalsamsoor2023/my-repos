<?php

namespace App\Http\Requests\Announcement;

use App\Enums\GeneralStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
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
            'residence_id' => 'required|integer|exists:residences,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:3000',
            'is_active' => 'required|integer|in:'.GeneralStatus::ACTIVE->value.','.GeneralStatus::INACTIVE->value,
            'created_by' => 'required|integer|exists:users,id',
            'updated_by' => 'required|integer|exists:users,id',
            'images.*' => 'nullable|mimes:png,jpg,jpeg,gif,webp|max:20480',
            'attachment' => 'nullable|mimes:pdf|max:20480',
        ];
    }
}
