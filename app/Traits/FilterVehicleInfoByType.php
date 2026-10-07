<?php

namespace App\Traits;

use App\Enums\Vehicle\VehicleType;
use Illuminate\Database\Eloquent\Builder;

trait FilterVehicleInfoByType
{
    public function applyVehicleFilters(Builder $query, array $data): Builder
    {
        return $query
            ->when(! empty($data['fuel_type']), fn ($q) => $q->whereIn('fuel_type', (array) $data['fuel_type']))
            ->when(! empty($data['body_type']), fn ($q) => $q->whereHas('vehicleModel', fn ($q) => $q->whereIn('body_type', (array) $data['body_type'])))
            ->when(! empty($data['insurance_company']), fn ($q) => $q->where('insurance_company_id', $data['insurance_company']))
            ->when(! empty($data['age_category']), function (Builder $q) use ($data) {
                $now = now()->year;

                return $q->when($data['age_category'] !== 'not_set', function (Builder $q) use ($data, $now) {
                    [$min, $max] = match ($data['age_category']) {
                        '1-5' => [1, 5],
                        '6-10' => [6, 10],
                        '11-15' => [11, 15],
                        '16-20' => [16, 20],
                        '21-25' => [21, 25],
                        '26-30' => [26, 30],
                        '31-35' => [31, 35],
                        '36-40' => [36, 40],
                        '41-45' => [41, 45],
                        '46-50' => [46, 50],
                        '50+' => [51, 100],
                        default => [null, null],
                    };

                    return $q->whereBetween('model_year', [$now - $max, $now - $min]);
                }, fn (Builder $q) => $q->whereNull('model_year'));
            });
    }

    public function filterByVehicleType(Builder $query, array $data, VehicleType $type): Builder
    {
        return $this->applyVehicleFilters(
            $query->whereHas('vehicleModel', fn ($q) => $q->where('type', $type)),
            $data
        );
    }
}
