<?php

namespace App\Http\Requests\Vehicle;

use App\Enums\GeneralSwitch;
use App\Enums\User\RoleType;
use App\Enums\Vehicle\FuelType;
use App\Enums\Vehicle\VehicleType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'province_id' => 'required|integer|exists:thailand_provinces,id',
            'unit_id' => 'required|integer|exists:units,id',
            'user_id' => [
                'required', 'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $users = User::whereHas('roles', function ($query) {
                        $query->whereIn('name', [RoleType::UNIT_OWNER->value, RoleType::UNIT_TENANT->value]);
                    })->whereHas('units', function (Builder $builder) use ($request) {
                        return $builder->where('unit_user.unit_id', $request->unit_id);
                    })->pluck('id')->toArray();

                    $check_is_unit_user_exist = in_array((int) $value, $users, true);

                    if ($check_is_unit_user_exist == false) {
                        return $fail($attribute.' is invalid.');
                    }
                },
            ],
            'vehicle_model_id' => 'required_without:model_others|integer|exists:vehicle_models,id',
            'inurance_company_id' => 'nullable|integer|exists:companies,id',
            'plate_number' => 'required|string|max:255',
            'purchase_year' => 'nullable|integer|digits:4|min:1900|max:'.(date('Y') + 1),
            'model_year' => 'nullable|integer|digits:4|min:1900|max:'.(date('Y') + 1),
            'roadtax_expiry_date' => 'nullable',
            'policy_no' => 'nullable|string|max:255',
            'is_access_card' => 'required|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
            'is_car_sticker' => 'required|integer|in:'.GeneralSwitch::ON->value.','.GeneralSwitch::OFF->value,
            'image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'front_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'back_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'right_side_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'left_side_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
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
