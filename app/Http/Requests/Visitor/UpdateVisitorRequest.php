<?php

namespace App\Http\Requests\Visitor;

use App\Models\VisitorCard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitorRequest extends FormRequest
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
            'leave_time' => 'nullable|date|after_or_equal:today',
            'visitor_card_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    $cacheKey = "visitor_cards:exists:{$value}";
                    $exists = Cache::remember($cacheKey, now()->addSeconds(120), function () use ($value) {
                        return VisitorCard::whereKey($value)->exists();
                    });

                    if (! $exists) {
                        $fail('The selected visitor card is invalid.');
                    }
                },
            ],
        ];
    }
}
