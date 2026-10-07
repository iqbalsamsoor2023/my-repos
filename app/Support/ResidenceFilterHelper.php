<?php

namespace App\Support;

use App\Services\ThailandLocationService;

class ResidenceFilterHelper
{
    /**
     * Resolve subdistrict IDs from province/district/subdistrict filters.
     *
     * @return array{ids: array, hasFilters: bool}
     */
    public static function resolveSubdistrictIds(array $provinceFilters): array
    {
        $subdistrictIds = array_values(array_filter((array) ($provinceFilters['subdistrict'] ?? [])));
        $districtIds = array_values(array_filter((array) ($provinceFilters['district'] ?? [])));
        $provinceIds = array_values(array_filter((array) ($provinceFilters['province'] ?? [])));

        $hasFilters = ! empty($subdistrictIds) || ! empty($districtIds) || ! empty($provinceIds);

        if (! empty($subdistrictIds)) {
            return [
                'ids' => array_values(array_unique($subdistrictIds)),
                'hasFilters' => true,
            ];
        }

        if (! empty($districtIds)) {
            $subdistrictIds = array_map(
                'intval',
                array_keys(ThailandLocationService::getSubdistrictsByDistricts(array_map('intval', $districtIds)))
            );

            return [
                'ids' => array_values(array_unique($subdistrictIds)),
                'hasFilters' => true,
            ];
        }

        if (! empty($provinceIds)) {
            $districtIds = array_map(
                'intval',
                array_keys(ThailandLocationService::getDistrictsByProvinces(array_map('intval', $provinceIds)))
            );

            if (! empty($districtIds)) {
                $subdistrictIds = array_map(
                    'intval',
                    array_keys(ThailandLocationService::getSubdistrictsByDistricts($districtIds))
                );
            }

            return [
                'ids' => array_values(array_unique($subdistrictIds)),
                'hasFilters' => true,
            ];
        }

        return [
            'ids' => [],
            'hasFilters' => $hasFilters,
        ];
    }
}
