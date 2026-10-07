<?php

namespace App\Http\Requests\Parcel;

use App\Enums\Parcel\ParcelStatus;
use App\Enums\Parcel\PickupType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateParcelRequest extends FormRequest
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
            'role_type' => 'required|integer|in:1,2,3',
            'parcel_id.*' => 'required|exists:parcels,id',
            'pickup_person_name' => 'required_if:status,==,'.ParcelStatus::PICKED_UP->value.'|string|max:255',
            'pickup_person_contact_no' => 'nullable',
            'status' => 'required|integer|in:'.ParcelStatus::PENDING_PICK_UP->value.','.ParcelStatus::PICKED_UP->value.','.ParcelStatus::NOT_MY_PARCEL->value,
            'pickup_type' => 'required_if:status,==,'.ParcelStatus::PICKED_UP->value.'|integer|in:'.PickupType::RECIPIENT->value.','.PickupType::ON_BEHALF->value,
            'signature_image' => 'sometimes|mimes:png,jpg,jpeg,gif|max:20480',
        ];
    }
}
