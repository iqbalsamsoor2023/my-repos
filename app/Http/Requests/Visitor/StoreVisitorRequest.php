<?php

namespace App\Http\Requests\Visitor;

use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\BlacklistType;
use App\Enums\Visitor\IdType;
use App\Enums\Visitor\VehicleType;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitorRequest extends FormRequest
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
        if ($this->visitor_purpose != 'VIP') {
            // Check if visitor_purpose is related to delivery/receive
            $isDeliveryPurpose = $this->isDeliveryRelatedPurpose($this->visitor_purpose);

            return [
                'name' => 'sometimes|string|max:255',
                'contact_no' => 'nullable|string|max:255',
                'id_type' => 'nullable|integer|in:'.IdType::IC->value.','.IdType::PASSPORT->value.','.IdType::DRIVING_LICENSE->value.','.IdType::OTHER->value,
                'id_number' => 'nullable|string|max:30',
                'blacklist_remark' => 'nullable|string|max:255',
                'visitor_card_id' => 'nullable|integer|exists:visitor_cards,id',
                'visitor_purpose' => 'nullable|string|max:255',
                'courier_logistic_partner_id' => [
                    'nullable',
                    'integer',
                    'exists:logistic_partners,id',
                    function ($attribute, $value, $fail) use ($isDeliveryPurpose) {
                        if ($value && !$isDeliveryPurpose) {
                            $fail('The courier logistic partner can only be set when visitor purpose is related to delivery/receive.');
                        }
                    },
                ],
                'food_delivery_logistic_partner_id' => [
                    'nullable',
                    'integer',
                    'exists:logistic_partners,id',
                    function ($attribute, $value, $fail) use ($isDeliveryPurpose) {
                        if ($value && !$isDeliveryPurpose) {
                            $fail('The food delivery logistic partner can only be set when visitor purpose is related to delivery/receive.');
                        }
                    },
                ],
                'company_name' => 'nullable|string|max:255',
                'arrival_type' => 'nullable|integer|in:'.ArrivalType::DRIVE_IN->value.','.ArrivalType::WALK_IN->value,
                'vehicle_type' => 'nullable|integer|in:'.VehicleType::CAR->value.','.VehicleType::TRUCK->value.','.VehicleType::MOTORBIKE->value.','
                    .VehicleType::VAN->value.','.VehicleType::TAXI->value.','.VehicleType::PICKUP->value,
                'vehicle_plate_no' => 'nullable|string|max:20',
                'temperature' => 'nullable',
                'passenger_count' => 'nullable|integer',
                'remark' => 'nullable|string|max:255',
                'is_allowed' => 'nullable|in:'.BlacklistType::ENTRY->value.','.BlacklistType::NO_ENTRY->value,
                'is_pre_register' => 'nullable|boolean',
                'id_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
                'sign_image' => 'nullable|mimes:png,jpg,jpeg,gif|max:20480',
                'residence_id' => 'nullable|integer|exists:residences,id',
                'visited_resident' => 'nullable',
                'visited_resident.*.unit_id' => 'nullable|exists:units,id',
                'visited_resident.*.user_id' => 'nullable|array|exists:users,id',
                'vehicle_info' => 'nullable',
            ];
        } else {
            return [];
        }
    }

    /**
     * Check if visitor purpose is related to delivery/receive.
     * Supports both English and Thai text.
     *
     * @param string|null $purpose
     * @return bool
     */
    protected function isDeliveryRelatedPurpose(?string $purpose): bool
    {
        if (!$purpose) {
            return false;
        }

        $deliveryKeywords = [
            'receive', 'delivery', 'courier', 'food', 'parcel',
            'รับ', 'ส่ง', 'ของ', 'พัสดุ', 'อาหาร', 'คูเรียร์'
        ];

        $purposeLower = mb_strtolower($purpose);

        foreach ($deliveryKeywords as $keyword) {
            if (mb_strpos($purposeLower, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }
}
