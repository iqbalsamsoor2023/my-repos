<?php

namespace App\Http\Requests\Maintenance;

use App\Models\Amenity;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'residence_id' => 'required_if:maintainable_id,==,"Others"',
            'maintainable_id' => 'required',
            'maintainable_type' => [
                'required',
                'string',
                'max:255',
                Rule::in([
                    'App\Models\Unit',
                    'App\Models\ResidenceAmenityOption',
                    'App\Models\ResidenceAmenity',
                    'App\Models\ClaimableItem', // dont remove. this to support backward compatibility
                ]),
            ],
            'claimable_title_id' => [
                Rule::requiredIf(fn () => $this->facilityHasClaimableTitle()),
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    $hasClaimableTitle = $this->facilityHasClaimableTitle();

                    // If claimable_title_id sent but amenity/facility does NOT have claimable title
                    if (! $hasClaimableTitle && $this->filled('claimable_title_id')) {
                        $fail('The claimable title id field is prohibited.');

                        return;
                    }

                    // If no claimable title, or no value sent, no further check needed
                    if (! $value) {
                        return;
                    }

                    $facilityId = $this->getFacilityAndAmenityId();

                    if (! $facilityId) {
                        $fail('Invalid Amenity/Facility ID');

                        return;
                    }

                    $isAssigned = DB::table('claimable_title_facility_and_amenity')
                        ->where('facility_and_amenity_id', $facilityId)
                        ->where('claimable_title_id', $value)
                        ->exists();

                    if (! $isAssigned) {
                        $fail('The selected claimable title is not assigned to this amenity/facility.');
                    }
                },
            ],
            // 'amenity_id' => [
            //     'required_if:maintainable_type,==,"App\\Models\\Unit"',
            //     function ($attribute, $value, $fail) use ($request) {
            //         $unit = Unit::whereId($request->maintainable_id)->first();

            //         $amenities = [];
            //         $amenities = Amenity::where('residence_id', $unit->residence_id)->pluck('id')->toArray();
            //         $amenities += ['Others' => 'Others'];

            //         if ($value != 'Others') {
            //             $check_is_amenity_exist = in_array((int) $value, $amenities, true);
            //         } else {
            //             $check_is_amenity_exist = in_array($value, $amenities, true);
            //         }

            //         if ($check_is_amenity_exist == false) {
            //             return $fail($attribute.' is invalid.');
            //         }
            //     },
            // ],
            'miscellaneous' => 'required_if:amenity_id,==,"Others"|required_if:maintainable_id,==,"Others"',
            'issue_description' => 'nullable|string|max:3000',
            'appointment_datetime' => 'date|after_or_equal:now',
            'images' => 'required|array',
            'images.*' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
            'reported_by' => 'required|integer|exists:users,id',
            'warranty_checker' => 'required_if:maintainable_type,==,"App\Models\Unit"',
        ];
    }

    protected function getFacilityAndAmenityId(): ?int
    {
        $type = $this->input('maintainable_type');
        $id = $this->input('maintainable_id');

        if ($type === 'App\Models\ResidenceAmenity') {
            return DB::table('residence_amenity')
                ->where('id', $id)
                ->value('facility_and_amenity_id');
        }

        if ($type === 'App\Models\ResidenceAmenityOption') {
            return DB::table('residence_amenity_options AS rao')
                ->join('residence_amenity AS ra', 'rao.residence_amenity_id', '=', 'ra.id')
                ->where('rao.id', $id)
                ->value('ra.facility_and_amenity_id');
        }

        return null;
    }

    protected function facilityHasClaimableTitle(): bool
    {
        $facilityId = $this->getFacilityAndAmenityId();

        return $facilityId && DB::table('claimable_title_facility_and_amenity')
            ->where('facility_and_amenity_id', $facilityId)
            ->exists();
    }
}
