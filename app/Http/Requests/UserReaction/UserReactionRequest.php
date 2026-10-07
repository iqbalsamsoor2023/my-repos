<?php

namespace App\Http\Requests\UserReaction;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\UserReaction\ReactableTypeEnum;
use App\Enums\UserReaction\ReactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserReactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reactable_id' => 'required',
            'reactable_type' => [
                'required',
                Rule::in(array_column(ReactableTypeEnum::cases(), 'value')),
            ],
            'user_id' => 'required',
            'reaction_type' => [
                'required',
                Rule::in(array_column(ReactionTypeEnum::cases(), 'value')),
            ],
        ];
    }
}
