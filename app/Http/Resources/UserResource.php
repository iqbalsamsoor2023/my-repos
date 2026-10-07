<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'name' => $this->name,
            'email' => $this->email,
            'id_number' => $this->id_number,
            'phone_no' => $this->phone_no,
            'date_of_birth' => $this->date_of_birth
                ? Carbon::parse($this->date_of_birth)->format('Y-m-d')
                : null,
            'gender' => $this->gender,
            'passport_number' => $this->passport_number,
            'passport_expiry' => $this->passport_expiry,
            'profile_image_url' => $this->profile_image_url,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]);
            }),
            'unit_users' => $this->whenLoaded('unitUsers', function () {
                return $this->unitUsers->map(function ($unitUser) {

                    $unit = $unitUser->relationLoaded('unit')
                        ? $unitUser->unit
                        : null;

                    $residence = $unit && $unit->relationLoaded('residence')
                        ? $unit->residence
                        : null;

                    $subdistrict = $residence && $residence->relationLoaded('subdistrict')
                        ? $residence->subdistrict
                        : null;

                    $district = $subdistrict && $subdistrict->relationLoaded('district')
                        ? $subdistrict->district
                        : null;

                    $developer = $residence && $residence->relationLoaded('developer')
                        ? $residence->developer
                        : null;

                    return [
                        'id' => $unitUser->id,
                        'unit_id' => $unitUser->unit_id,
                        'user_id' => $unitUser->user_id,
                        'is_owner' => $unitUser->is_owner,
                        'mmb_id' => $unitUser->mmb_id,
                        'is_main_owner' => $unitUser->is_main_owner,
                        'is_main_tenant' => $unitUser->is_main_tenant,
                        'relationship' => $unitUser->relationship,

                        'unit' => $unit ? [
                            'home_id' => $unit->home_id,
                            'unit_number' => $unit->unit_number,
                            'street' => $unit->street,
                            'floor' => $unit->floor,
                            'block' => $unit->block,

                            'residence' => $residence ? [
                                'id' => $residence->id,
                                'name' => $residence->name,
                                'name_th' => $residence->name_th,
                                'mooban_type' => $residence->mooban_type,
                                'latitude' => $residence->latitude,
                                'longitude' => $residence->longitude,
                                'image_url' => $residence->image_url,
                                'logo_url' => $residence->logo_url,

                                'subdistrict' => $subdistrict ? [
                                    'id' => $subdistrict->id,
                                    'name_in_thai' => $subdistrict->name_in_thai,
                                    'name_in_english' => $subdistrict->name_in_english,

                                    'district' => $district ? [
                                        'id' => $district->id,
                                        'name_in_thai' => $district->name_in_thai,
                                        'name_in_english' => $district->name_in_english,
                                    ] : null,

                                ] : null,

                                'developer' => $developer ? [
                                    'id' => $developer->id,
                                    'name' => $developer->name,
                                    'name_th' => $developer->name_th,
                                    'email' => $developer->contact_email,
                                    'contact_number' => $developer->contact_number,
                                    'image_url' => $developer->image_url,
                                ] : null,

                            ] : null,

                        ] : null,
                    ];
                });
            }),
            'property_management' => $this->when(
                $this->relationLoaded('propertyManagement') &&
                $this->roles->contains(fn ($r) => $r->name === 'Property Management'),
                function () {
                    $pm = $this->propertyManagement;

                    $subdistrict = $pm && $pm->relationLoaded('subdistrict')
                        ? $pm->subdistrict
                        : null;

                    $district = $subdistrict && $subdistrict->relationLoaded('district')
                        ? $subdistrict->district
                        : null;

                    $developer = $pm && $pm->relationLoaded('developer')
                        ? $pm->developer
                        : null;

                    return [
                        'id' => $pm->id,
                        'name' => $pm->name,
                        'name_th' => $pm->name_th,
                        'latitude' => $pm->latitude,
                        'longitude' => $pm->longitude,

                        'district_id' => $subdistrict?->district_id,
                        'district_name' => $district?->name_in_english,
                        'district_name_th' => $district?->name_in_thai,

                        'subdistrict_id' => $pm->subdistrict_id,
                        'subdistrict_name' => $subdistrict?->name_in_english,
                        'subdistrict_name_th' => $subdistrict?->name_in_thai,

                        'cover_image_url' => $pm->image_url ?? 'https://dashboard.mymooban.co.th/images/no-image.png',
                        'developer_logo_url' => $developer?->image_url ?? 'https://dashboard.mymooban.co.th/images/no-image.png',
                    ];
                }
            ),
        ];
    }
}