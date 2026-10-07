<?php

namespace App\Http\Resources\Auth;

use App\Models\Residence;
use App\Models\UnitUser;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language');
        $isThai = strtolower($lang) === 'th';

        $rolesWithResidence = ['Property Management'];
        $roleMultipleResidence = ['Unit Owner', 'Unit Tenant'];

        $roleNames = $this->roles->pluck('name')->toArray();

        $hasSingleResidenceRole = array_intersect($roleNames, $rolesWithResidence);
        $hasMultipleResidenceRole = array_intersect($roleNames, $roleMultipleResidence);

        $response = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_no' => $this->phone_no,
            'profile_image_url' => $this->profile_image_url,
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            })->values(),
        ];

        // For multiple residences (Owner/Tenant)
        if (! empty($hasMultipleResidenceRole)) {
            $unitUsers = UnitUser::with('unit')->where('user_id', $this->id)->get();
            $residenceIds = $unitUsers->pluck('unit.residence_id')->unique()->values();
            $residenceModels = Residence::with('developer')->whereIn('id', $residenceIds)->get();

            $residences = $residenceModels->map(function ($residence) use ($isThai) {
                return [
                    'id' => $residence->id,
                    'name' => $isThai
                        ? ($residence->name_th ?? $residence->name)
                        : ($residence->name ?? null),
                    'latitude' => $residence->latitude,
                    'longitude' => $residence->longitude,
                    'district_id' => $residence?->subdistrict->district_id ?? null,
                    'district_name' => $isThai
                        ? ($residence?->subdistrict?->district?->name_in_thai ?? $residence?->subdistrict?->name_in_english)
                        : ($residence?->subdistrict?->district?->name_in_english ?? null),
                    'subdistrict_name' => $isThai
                        ? ($residence?->subdistrict?->name_in_thai ?? $residence?->subdistrict?->name_in_english)
                        : ($residence?->subdistrict?->name_in_english ?? null),
                    'cover_image_url' => $residence->image_url,
                    'developer_logo_url' => $residence?->developer?->image_url,
                ];
            })->values();

            $response['residences'] = $residences;
        }

        // For single residence (Property Management)
        if (! empty($hasSingleResidenceRole)) {
            $residence = Residence::where('property_management_user_id', $this->id)->first();

            if ($residence) {
                $response['residence'] = [
                    'id' => $residence->id,
                    'name' => $isThai
                        ? ($residence->name_th ?? $residence->name)
                        : ($residence->name ?? null),
                    'latitude' => $residence->latitude,
                    'longitude' => $residence->longitude,
                    'district_id' => $residence?->subdistrict->district_id ?? null,
                    'district_name' => $isThai
                        ? ($residence?->subdistrict?->district?->name_in_thai ?? $residence?->subdistrict?->name_in_english)
                        : ($residence?->subdistrict?->district?->name_in_english ?? null),
                    'subdistrict_name' => $isThai
                        ? ($residence?->subdistrict?->name_in_thai ?? $residence?->subdistrict?->name_in_english)
                        : ($residence?->subdistrict?->name_in_english ?? null),
                    'cover_image_url' => $residence->image_url,
                    'developer_logo_url' => $residence?->developer?->image_url,
                ];
            }
        }

        return $response;
    }
}
