<?php

namespace App\Http\Resources\Visitor;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitingArrangementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'unit_id' => $this->unit_id,
            'visitor_log_id' => $this->visitor_log_id,
            'feedback_remark' => $this->feedback_remark,
            'status' => $this->status,
            'residence_id' => $this->residence_id,
            'estamp_status' => $this->estamp_status,
            'user_estamp_status' => $this->user_estamp_status,
            'estamp_by' => [
                'id' => $this?->estampBy?->id,
                'name' => $this?->estampBy?->name,
            ],
            'estamp_by_type' => $this->estamp_by_type,
            'visitor_log' => [
                'id' => $this?->visitorLog?->id,
                'is_pre_register' => $this?->visitorLog?->is_pre_register,
                'qr_code_url' => $this?->visitorLog?->qr_code_url,
                'visitor_generated_no' => $this->visitor_generated_no,
                'visitor_purpose' => $this?->visitorLog?->visitor_purpose,
                'vehicle_plate_no' => $this?->visitorLog?->vehicle_plate_no,
                'arrival_type' => $this?->visitorLog?->arrival_type,
                'vehicle_type' => $this?->visitorLog?->vehicle_type,
                'arrival_time' => $this?->visitorLog?->arrival_time,
                'leave_time' => $this?->visitorLog?->leave_time,
                'remark' => $this?->visitorLog?->remark,
                'blacklist_remark' => $this?->visitorLog?->blacklist_remark,
                'visitor' => [
                    'id' => $this?->visitorLog?->visitor?->id,
                    'name' => $this?->visitorLog?->visitor?->name,
                    'contact_no' => $this?->visitorLog?->visitor?->contact_no,
                    'id_number' => $this?->visitorLog?->visitor?->id_number,
                ],
                'visiting_arrangements' => $this->visitorLog->visitingArrangements->map(function($arrangement) {
                    return [
                        'id' => $arrangement->id,
                        'visitor_log_id' => $arrangement->visitor_log_id,
                        'residence_id' => $arrangement->residence_id,
                        'unit_id' => $arrangement->unit_id,
                        'user_id' => $arrangement->user_id,
                        'estamp_by' => [
                            'id' => $arrangement?->estampBy?->id,
                            'name' => $arrangement?->estampBy?->name,
                            'email' => $arrangement?->estampBy?->email,
                            'phone_no' => $arrangement?->estampBy?->phone_no,

                        ],
                        'estamp_by_type' => $arrangement->estamp_by_type,
                        'status' => $arrangement->status,
                        'feedback_remark' => $arrangement->feedback_remark,
                        'stamp_by_name' => $arrangement->stamp_by_name,
                        'estamp_status' => $arrangement->estamp_status,
                        'user_estamp_status' => $arrangement->user_estamp_status,
                    ];
                }),
            ] ,  
            'unit' => [
                'unit_number' => $this?->unit?->unit_number,
            ],
            'user' => [
                'id' => $this?->user?->id,
                'name' => $this?->user?->name,
            ],
        ];

        return $data;
    }
}