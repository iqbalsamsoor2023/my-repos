<?php

namespace App\Http\Requests\PreregisterVisitor;

use App\Enums\GeneralSwitch;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\IdType;
use App\Enums\Visitor\VehicleType;
use Illuminate\Foundation\Http\FormRequest;

class StorePreregisterVisitorRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:255',
            'id_type' => 'required|integer|in:'.IdType::IC->value.','.IdType::PASSPORT->value.','.IdType::DRIVING_LICENSE->value,
            'id_number' => 'required_if:id_type,==,'.IdType::IC->value.'|string|max:13',
            'visitor_purpose' => 'required|string|max:255',
            'arrival_type' => 'required|integer|in:'.ArrivalType::DRIVE_IN->value.','.ArrivalType::WALK_IN->value,
            'vehicle_type' => 'required_if:arrival_type,==,1|integer|in:'.VehicleType::CAR->value.','.VehicleType::TRUCK->value.','.VehicleType::MOTORBIKE->value.','
                .VehicleType::VAN->value.','.VehicleType::TAXI->value.','.VehicleType::PICKUP->value,
            'vehicle_plate_no' => 'required_if:arrival_type,==,'.ArrivalType::DRIVE_IN->value.'|string|max:20',
            'is_multiple_entry' => 'required|boolean',
            'validity_start_date' => 'required|date|after_or_equal:today',
            'validity_end_date' => 'required_if:is_multiple_entry, ==, true|date|after_or_equal:validity_start_date',
            'unit_id' => 'required|exists:units,id',
            'user_id' => 'required|exists:users,id',
            'is_qr_code_expired' => 'nullable|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
        ];
    }
}
