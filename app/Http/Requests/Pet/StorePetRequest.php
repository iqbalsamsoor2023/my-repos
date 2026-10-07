<?php

namespace App\Http\Requests\Pet;

use App\Enums\Pet\PetType;
use Illuminate\Foundation\Http\FormRequest;

class StorePetRequest extends FormRequest
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
            'breed' => 'required|string|max:255',
            'type' => 'required|integer|in:'.PetType::CAT->value.','.PetType::DOG->value,
            'year' => 'required|integer|digits:4|min:1900|max:'.(date('Y') + 1),
            'created_by' => 'required|integer|exists:users,id',
            'image_front' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'image_right' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'image_back' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'image_left' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'image_top' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
            'image_bottom' => 'required|mimes:png,jpg,jpeg,gif|max:20480',
        ];
    }
}
