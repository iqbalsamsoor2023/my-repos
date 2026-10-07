<?php

namespace App\Http\Resources\Visitor;

use App\Support\VisitorPurposeNormalizer;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class VisitorLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $purpose = trim((string) $this->visitor_purpose);

        // Map default visitor purpose only
        $defaultOptions = [
            // Receive & Delivery
            'Receive & Delivery' => __('visitor.receive_or_delivery'),
            'Receive/Delivery'   => __('visitor.receive_or_delivery'),
            'Receive / Delivery' => __('visitor.receive_or_delivery'),
            'รับของ/ส่งของ' => __('visitor.receive_delivery'),
            'รับของ / ส่งของ' => __('visitor.receive_delivery'),
        
            // Dropoff / Pickup
            'Drop Off/Pick Up' => __('visitor.dropoff_or_pickup'),
            'Drop Off / Pick Up' => __('visitor.dropoff_or_pickup'),
            'รับ-ส่งคน/แท็กซี่' => __('visitor.dropoff_or_pickup'),
        
            // Contractor
            'Contractor / Worker' => __('visitor.contractor_or_worker'),
            'ผู้รับเหมา/คนงาน' => __('visitor.contractor_or_worker'),
        
            // Visitor Parking
            'Visitor Parking' => __('visitor.visitor_parking'),
            'มาติดต่อ' => __('visitor.visitor_parking'),
        
            // VIP
            'VIP' => __('visitor.vip'),
            'วีไอพี' => __('visitor.vip'),
        ];

        // Fallback: just show normalized label if not in the map
        $translatedPurpose = $defaultOptions[$purpose] ?? $purpose;
        
        $data = [
            'id' => $this->id,
            'visitor_id' => $this->visitor_id,
            'visitor_id_number' => $this->visitor?->id_number ?? '',
            'residence_id' => $this->residence_id,
            'visitor_card_id' => $this->visitor_card_id,
            'visitor_purpose' => $translatedPurpose,
            'visitor_code' => $this->visitor_code,
            'visitor_generated_no' => $this->visitor_generated_no,
            'company_name' => $this->company_name,
            'arrival_type' => $this->arrival_type,
            'vehicle_type' => $this->vehicle_type,
            'vehicle_plate_no' => $this->vehicle_plate_no,
            'arrival_time' => $this->arrival_time,
            'leave_time' => $this->leave_time,
            'temperature' => $this->temperature,
            'passenger_count' => $this->passenger_count,
            'remark' => $this->remark,
            'blacklist_remark' => $this->blacklist_remark,
            'is_allowed' => $this->is_allowed,
            'is_pre_register' => $this->is_pre_register,
            'vehicle_info' => $this->vehicle_info,
            'qr_visibility' => $this->qr_visibility,
            'qr_code_url' => $this->qr_code_url,
            'pdpa_status' => $this->pdpa_status,
            'estamp_status_value' => $this->estamp_status['value'],
            'estamp_status' => $this->estamp_status['label'],
            'esign_image_url' => $this->esign_image_url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'unit_visits' => optional($this->visitingArrangements)
                ?->pluck('unit.unit_number')
                ?->unique()
                ?->values(),
            'visiting_arrangements' => $this->visitingArrangements->map(function ($visitingArrangement) {
                return [
                    'id' => $visitingArrangement->id,
                    'visitor_log_id' => $visitingArrangement->visitor_log_id,
                    'unit_id' => $visitingArrangement->unit_id,
                    'user_id' => $visitingArrangement->user_id,
                    'residence_id' => $visitingArrangement->residence_id,
                    'status' => $visitingArrangement->status,
                    'estamp_by' => $visitingArrangement->estamp_by,
                    'estamp_by_type' => $visitingArrangement->estamp_by_type,
                    'feedback_remark' => $visitingArrangement->feedback_remark,
                    'stamp_by_name' => $visitingArrangement->stamp_by_name,
                    'estamp_status' => $visitingArrangement->estamp_status,
                    'user_estamp_status' => $visitingArrangement->user_estamp_status,
                    'unit' => [
                        'id' => $visitingArrangement?->unit?->id,
                        'unit_number' => $visitingArrangement?->unit?->unit_number,
                        'block' => $visitingArrangement?->unit?->block,
                        'booking_form_pdf_url' => $visitingArrangement?->unit?->booking_form_pdf_url,
                        'house_contract_url' => $visitingArrangement?->unit?->house_contract_url,
                        'floor_plan_url' => $visitingArrangement?->unit?->floor_plan_url,
                    ],
                    'user' => [
                        'id' => $visitingArrangement?->user?->id,
                        'name' => $visitingArrangement?->user?->name,
                        'profile_image_url' => $visitingArrangement?->user?->profile_image_url,
                    ],
                    'residence' => [
                        'id' => $visitingArrangement?->residence?->id,
                        'image_url' => $visitingArrangement?->residence?->image_url,
                        'logo_url' => $visitingArrangement?->residence?->logo_url,
                        'visitor_setting' => [
                            'id' => $visitingArrangement?->residence?->visitorSetting?->id,
                            'residence_id' => $visitingArrangement?->residence?->visitorSetting?->residence_id,
                            'is_qr_active' => $visitingArrangement?->residence?->visitorSetting?->is_qr_active,
                            'pdpa_url' => $visitingArrangement?->residence?->visitorSetting?->pdpa_url,
                        ],
                    ],

                ];
            }),
            'visitor' => [
                'id' => $this->visitor?->id ?? '',
                'name' => $this->visitor?->name ?? '',
                'id_type' => $this->visitor?->id_type ?? '',
                'id_number' => $this->visitor?->id_number ?? '',
                'contact_no' => $this->visitor?->contact_no ?? '',
            ],
            'visitor_card' => $this->visitorCard ? [
                'id' => $this->visitorCard->id,
                'residence_id' => $this->visitorCard->residence_id,
                'visitor_card_no' => $this->visitorCard->visitor_card_no,
                'is_custom' => $this->visitorCard->is_custom,
            ] : null,
        ];

        return $data;
    }
}
