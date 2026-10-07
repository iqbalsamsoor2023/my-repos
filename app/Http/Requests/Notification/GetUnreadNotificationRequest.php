<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class GetUnreadNotificationRequest extends FormRequest
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
            'unit_id' => 'nullable|integer|exists:units,id',
            'module' => 'nullable|string|in:Parcel,Announcement,Maintenance,Event,Support Ticket,Visitor,Notification',
        ];
    }
}
