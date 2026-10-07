<?php

namespace App\Http\Requests\Visitor;

use Illuminate\Foundation\Http\FormRequest;

class CreateUpdateVisitorRequest extends FormRequest
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
            'visitor_card_id' => 'required_without:visitor_code|integer|exists:visitor_cards,id',
            'visitor_code' => 'required_without:visitor_card_id|string',
        ];
    }
}
