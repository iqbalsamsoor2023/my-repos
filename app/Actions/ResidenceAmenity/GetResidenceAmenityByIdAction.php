<?php

namespace App\Actions\ResidenceAmenity;

use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;

class GetResidenceAmenityByIdAction
{
    public function execute(int $id, string $modelClass)
    {
        return match ($modelClass) {
            ResidenceAmenityOption::class => ResidenceAmenityOption::with([
                'residenceAmenity',
                'amenityRate',
                'amenityTimeslots' => fn ($q) => $q->where('is_active', true),
                'amenityBookings',
            ])->where('is_active', true)->findOrFail($id),

            default => ResidenceAmenity::with([
                'facilityAndAmenity',
                'amenityRate',
                'residenceAmenityOptions' => fn ($q) => $q->where('is_active', true),
                'amenityTimeslots' => fn ($q) => $q->where('is_active', true),
                'amenityBookings',
            ])
                ->where('is_active', true)
                ->whereDoesntHave('residenceAmenityOptions')
                ->findOrFail($id),
        };
    }
}
