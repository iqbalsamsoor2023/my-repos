<?php

namespace App\Http\Requests\AmenityBooking;

use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAmenityBookingRequest extends FormRequest
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
            'amenity_bookable_id' => 'required|integer',
            'amenity_bookable_type' => 'required|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'ref_no' => 'nullable|string|max:20',
            'start_at' => 'required|date|after_or_equal:today',
            'end_at' => 'required|date|after:start_at',
            'created_by' => 'required|integer|exists:users,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('amenity_bookable_type');
            $id = $this->input('amenity_bookable_id');

            $allowedTypes = [
                ResidenceAmenity::class,
                ResidenceAmenityOption::class,
            ];

            if (! in_array($type, $allowedTypes)) {
                $validator->errors()->add('amenity_bookable_type', 'The selected amenity_bookable_type is invalid.');

                return;
            }

            if (! class_exists($type)) {
                $validator->errors()->add('amenity_bookable_type', 'Invalid model specified.');

                return;
            }

            $model = $type::find($id);

            if (! $model) {
                $validator->errors()->add('amenity_bookable_id', 'Amenity not found.');

                return;
            }
        });
    }
}
