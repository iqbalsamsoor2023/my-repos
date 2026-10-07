<?php

namespace App\Http\Requests\FacilityBooking;

use App\Enums\FacilityBooking\FacilityBookingStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilityBookingRequest extends FormRequest
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
            'status' => 'required|integer|in:'.FacilityBookingStatus::PENDING->value.','.FacilityBookingStatus::APPROVED->value.','.FacilityBookingStatus::REJECT->value.','.FacilityBookingStatus::COMPLETE->value.','.FacilityBookingStatus::NO_SHOW->value,
        ];
    }
}
