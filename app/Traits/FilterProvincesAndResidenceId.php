<?php

namespace App\Traits;

use App\Services\ThailandLocationService;
use App\Support\QueryGuardSupport;
use Illuminate\Database\Eloquent\Builder;

trait FilterProvincesAndResidenceId
{
    /**
     * Apply province and mooban/residence filters to a query.
     * Optimized for performance and supporting multiple filter types.
     *
     * @param  array  $provinceFilters  Province/Location filters (province, district, subdistrict, main_road)
     * @param  array  $moobanFilters  Mooban filters (mooban_type, sub_type, residence_id)
     */
    public function applyProvincesAndResidenceFilters(Builder $query, array $provinceFilters = [], array $moobanFilters = []): Builder
    {
        // Apply province/location filters
        $provinceIds = array_map('intval', array_filter((array) ($provinceFilters['province'] ?? [])));
        $districtIds = array_map('intval', array_filter((array) ($provinceFilters['district'] ?? [])));
        $subdistrictIds = array_map('intval', array_filter((array) ($provinceFilters['subdistrict'] ?? [])));
        $residenceIds = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds, $subdistrictIds);
        $hasLocationFilters = ! empty($provinceIds) || ! empty($districtIds) || ! empty($subdistrictIds);

        $query->when(
            $hasLocationFilters,
            function (Builder $q) use ($residenceIds): Builder {
                return QueryGuardSupport::whereHasInOrDenyAll($q, 'unit.residence', 'id', $residenceIds);
            }
        )
            ->when(
                ! empty($provinceFilters['main_road']),
                fn ($q) => $q->whereHas(
                    'unit.residence',
                    fn ($q) => $q->where('main_road', 'LIKE', '%'.$provinceFilters['main_road'].'%')
                )
            );

        // Apply mooban/residence filters
        $query->when(
            ! empty($moobanFilters['mooban_type']),
            fn ($q) => $q->whereHas(
                'unit.residence',
                fn ($q) => $q->whereIn('mooban_type', (array) $moobanFilters['mooban_type'])
            )
        )
            ->when(
                ! empty($moobanFilters['sub_type']),
                fn ($q) => $q->whereHas(
                    'unit.residence',
                    fn ($q) => $q->whereIn('sub_type', (array) $moobanFilters['sub_type'])
                )
            )
            ->when(
                ! empty($moobanFilters['residence_id']),
                fn ($q) => $q->whereHas(
                    'unit.residence',
                    fn ($q) => $q->where('id', $moobanFilters['residence_id'])
                )
            );

        return $query;
    }

    /**
     * Apply residence activation status filter when provided.
     *
     * @param  array  $statusFilters  Residence activation status IDs
     */
    public function applyResidenceActivationStatusFilters(Builder $query, array $statusFilters = []): Builder
    {
        return $query->when(
            ! empty($statusFilters),
            fn ($q) => $q->whereHas(
                'unit.residence',
                fn ($q) => $q->whereIn('residence_activation_status_id', (array) $statusFilters)
            )
        );
    }
}
