<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'residence_id' => $this->residence_id,
            'invitation_code_owner' => $this->invitation_code_owner,
            'invitation_code_tenant' => $this->invitation_code_tenant,
            'home_id' => $this->home_id,
            'myseevr_link' => $this->myseevr_link,
            'unit_number' => $this->unit_number,
            'street' => $this->street,
            'floor' => $this->floor,
            'block' => $this->block,
            'status' => $this->status,
            'move_in_at' => $this->move_in_at,
            'booking_form_pdf_url' => $this->booking_form_pdf_url,
            'house_contract_url' => $this->house_contract_url,
            'floor_plan_pdf_url' => $this->floor_plan_pdf_url,
            'floor_plan_url' => $this->floor_plan_url,
            'invitation_code_type' => $this->invitation_code_type,
            'residence' => [
                'id' => $this?->residence?->id,
                'name' => $this->residence?->name,
                'name_th' => $this->residence?->name_th,
                'google_location_link' => $this->residence?->google_location_link,
                'latitude' => $this->residence?->latitude,
                'longitude' => $this->residence?->longitude,
                'subdistrict_id' => $this->residence?->subdistrict_id,
                'developer_id' => $this->residence?->developer_id,
                'developer_user_id' => $this->residence?->developer_user_id,
                'person_in_charges' => $this->residence?->person_in_charges,
                'juristic_details' => $this->residence?->juristic_details,
                'company_id' => $this->residence?->company_id,
                'image_url' => $this->residence?->image_url,
                'subdistrict' => [
                    'id' => $this->residence?->subdistrict?->id,
                    'name_in_thai' => $this->residence?->subdistrict?->name_in_thai,
                    'name_in_english' => $this->residence?->subdistrict?->name_in_english,
                    'district' => [
                        'id' => $this->residence?->subdistrict?->district?->id,
                        'name_in_thai' => $this->residence?->subdistrict?->district?->name_in_thai,
                        'name_in_english' => $this->residence?->subdistrict?->district?->name_in_english,
                        'province' => [
                            'id' => $this->residence?->subdistrict?->district?->province?->id,
                            'name_in_thai' => $this->residence?->subdistrict?->district?->province?->name_in_thai,
                            'name_in_english' => $this->residence?->subdistrict?->district?->province?->name_in_english,
                        ],
                    ],
                ],
            ],
        ];
    }
}
