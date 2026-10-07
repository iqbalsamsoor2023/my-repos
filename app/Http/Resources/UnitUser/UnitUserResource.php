<?php

namespace App\Http\Resources\UnitUser;

use Carbon\Carbon;
use App\Enums\User\Gender;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_id' => (int) $this->unit_id,
            'user_id' => (int) $this->user_id,
            'is_owner' => $this->is_owner,
            'mmb_id' => $this->mmb_id,
            'is_main_owner' => $this->is_main_owner,
            'is_main_tenant' => $this->is_main_tenant,
            'relationship' => $this->relationship,
            'approval_status' => $this->approval_status,
            'unit' => [
                'id' => $this?->unit?->id,
                'residence_id' => $this?->unit?->residence_id,
                'home_id' => $this?->unit?->home_id,
                'unit_number' => $this?->unit?->unit_number,
                'move_in_at' => $this?->unit?->move_in_at,
                'booking_form_pdf_url' => $this?->unit?->booking_form_pdf_url,
                'house_contract_url' => $this?->unit?->house_contract_url,
                'floor_plan_pdf_url' => $this?->unit?->floor_plan_pdf_url,
                'residence' => [
                    'id' => $this?->unit?->residence?->id,
                    'name' => $this?->unit?->residence?->name,
                    'name_th' => $this?->unit?->residence?->name_th,
                    'latitude' => $this?->unit?->residence?->latitude,
                    'longitude' => $this?->unit?->residence?->longitude,
                    'support_ticket_status' => $this?->unit?->residence?->support_ticket_status,
                    'image_url' => $this?->unit?->residence?->image_url,
                    'subdistrict' => [
                        'id' => $this?->unit?->residence?->subdistrict?->id,
                        'name_in_english' => $this?->unit?->residence?->subdistrict?->name_in_english,
                        'name_in_thai' => $this?->unit?->residence?->subdistrict?->name_in_thai,
                        'district_id' => $this?->unit?->residence?->subdistrict?->district_id,
                        'district' => [
                            'id' => $this?->unit?->residence?->subdistrict?->district?->id,
                            'name_in_english' => $this?->unit?->residence?->subdistrict?->district?->name_in_english,
                            'name_in_thai' => $this?->unit?->residence?->subdistrict?->district?->name_in_thai,
                            'province' => [
                                'id' => $this?->unit?->residence?->subdistrict?->district?->province?->id,
                                'name_in_english' => $this?->unit?->residence?->subdistrict?->district?->province?->name_in_english,
                                'name_in_thai' => $this?->unit?->residence?->subdistrict?->district?->province?->name_in_thai,
                            ],
                        ],
                    ],
                    'developer' => [
                        'id' => $this?->unit?->residence?->developer?->id,
                        'mmb_user_id' => $this?->unit?->residence?->developer?->mmb_user_id,
                        'name' => $this?->unit?->residence?->developer?->name,
                        'name_th' => $this?->unit?->residence?->developer?->name_th,
                        'billing_address' => $this?->unit?->residence?->developer?->billing_address,
                        'billing_address' => $this?->unit?->residence?->developer?->billing_address,
                        'province_id' => $this?->unit?->residence?->developer?->province_id,
                        'thailand_district_id' => $this?->unit?->residence?->developer?->thailand_district_id,
                        'thailand_sub_district_id' => $this?->unit?->residence?->developer?->thailand_sub_district_id,
                        'contact_number' => $this?->unit?->residence?->developer?->contact_number,
                        'contact_email' => $this?->unit?->residence?->developer?->contact_email,
                        'activation_status_id' => $this?->unit?->residence?->developer?->activation_status_id,
                        'staff_rules_and_regulations' => $this?->unit?->residence?->developer?->staff_rules_and_regulations,
                        'image_url' => $this?->unit?->residence?->developer?->image_url,
                    ],
                    'property_management_user' => [
                        'id' => $this?->unit?->residence?->propertyManagementUser?->id,
                        'name' => $this?->unit?->residence?->propertyManagementUser?->name,
                        'email' => $this?->unit?->residence?->propertyManagementUser?->email,
                        'phone_no' => $this?->unit?->residence?->propertyManagementUser?->phone_no,
                        'profile_image_url' => $this?->unit?->residence?->propertyManagementUser?->profile_image_url,
                    ],
                    'residence_guard_user' => [
                        'id' => $this?->unit?->residence?->residenceGuardUser?->id,
                        'name' => $this?->unit?->residence?->residenceGuardUser?->name,
                        'email' => $this?->unit?->residence?->residenceGuardUser?->email,
                        'phone_no' => $this?->unit?->residence?->residenceGuardUser?->phone_no,
                        'profile_photo_path' => $this?->unit?->residence?->residenceGuardUser?->profile_photo_path,
                    ],
                ],
            ],
            'user' => [
                'id' => $this?->user?->id,
                'country_id' => $this?->user?->country_id,
                'name' => $this?->user?->name,
                'id_number' => $this?->user?->id_number,
                'phone_no' => $this?->user?->phone_no,
                'email_verified_at' => $this?->user?->email_verified_at,
                'date_of_birth' => isset($this->user) && !empty($this->user->date_of_birth)
                    ? Carbon::parse($this->user->date_of_birth)->format('Y-m-d')
                    : null,
                'gender' => isset($this->user->gender) && Gender::tryFrom((int) $this->user->gender)
                    ? Gender::from((int) $this->user->gender)->label(app()->getLocale()) // 'en' or 'th'
                    : null,
                'passport_number' => $this?->user?->passport_number,
                'passport_expiry' => $this?->user?->passport_expiry,
                'profile_image_url' => $this?->user?->profile_image_url,
            ],
        ];
    }
}
