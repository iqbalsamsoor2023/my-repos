<?php

namespace App\Http\Requests\Event;

use App\Enums\GeneralSwitch;
use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
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
            'id' => 'required|integer|exists:events,id',
            'user_id' => 'required|integer|exists:users,id',
            'is_going' => 'required|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
        ];
    }
}
