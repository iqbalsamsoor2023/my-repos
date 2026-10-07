<?php

namespace App\Http\Requests\Vehicle;

use App\Enums\GeneralSwitch;
use App\Enums\Vehicle\FuelType;
use App\Enums\Vehicle\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'vehicle_model_id' => 'nullable|integer|exists:vehicle_models,id',
            'inurance_company_id' => 'nullable|integer|exists:companies,id',
            'plate_number' => 'nullable|string|max:255',
            'purchase_year' => 'nullable|integer|digits:4|min:1900|max:'.(date('Y') + 1),
            'model_year' => 'nullable|integer|digits:4|min:1900|max:'.(date('Y') + 1),
            'roadtax_expiry_date' => 'nullable|after_or_equal:tomorrow',
            'policy_no' => 'nullable|string|max:255',
            'is_access_card' => 'nullable|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
            'is_car_sticker' => 'nullable|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
            'image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'roadtax_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'insurance_others' => 'nullable|string|max:255',
            'model_others' => 'nullable|string|max:255',
            'brand_id' => 'required_with:model_others|integer|exists:vehicle_brands,id',
            'vehicle_type' => 'required_with:model_others|integer|in:'.VehicleType::CAR->value.','.VehicleType::MOTORCYCLE->value,
            'fuel_type' => [
                'nullable',
                Rule::enum(FuelType::class),
            ],
        ];
    }
}
