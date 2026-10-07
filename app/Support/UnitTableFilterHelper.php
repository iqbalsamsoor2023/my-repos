<?php

namespace App\Support;

use App\Enums\Residence\MoobanType;
use App\Services\ThailandLocationService;
use Illuminate\Database\Eloquent\Builder;

class UnitTableFilterHelper
{
    /**
     * Apply ListUnits table filters to a Unit query.
     */
    public static function applyTableFilters(Builder $query, array $tableFilters = []): Builder
    {
        $moobanFilters = $tableFilters['mooban'] ?? [];
        $provinceFilters = $tableFilters['province_filters'] ?? [];
        $statusFilters = $tableFilters['status_filters'] ?? [];
        $unitSearchFilters = $tableFilters['unit_search'] ?? [];

        $moobanTypes = array_map(
            fn ($value) => $value instanceof MoobanType ? $value->value : $value,
            (array) ($moobanFilters['mooban_type'] ?? [])
        );

        $query->when(
            ! empty($moobanTypes),
            fn (Builder $query) => $query->whereHas(
                'residence',
                fn ($q) => $q->whereIn('mooban_type', $moobanTypes)
            )
        )
            ->when(
                ! empty($moobanFilters['sub_type']),
                fn (Builder $query) => $query->whereHas(
                    'residence',
                    fn ($q) => $q->whereIn('sub_type', (array) $moobanFilters['sub_type'])
                )
            )
            ->when(
                $moobanFilters['residence_id'] ?? null,
                fn (Builder $query, $value) => $query->where('residence_id', $value)
            )
            ->when(
                $moobanFilters['unit_number'] ?? null,
                fn (Builder $query, $value) => $query->where('unit_number', $value)
            );

        $provinceIds = array_map('intval', array_filter((array) ($provinceFilters['province'] ?? [])));
        $districtIds = array_map('intval', array_filter((array) ($provinceFilters['district'] ?? [])));
        $subdistrictIds = array_map('intval', array_filter((array) ($provinceFilters['subdistrict'] ?? [])));
        $residenceIds = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds, $subdistrictIds);
        $hasLocationFilters = ! empty($provinceIds) || ! empty($districtIds) || ! empty($subdistrictIds);

        $query->when(
            $hasLocationFilters,
            function (Builder $query) use ($residenceIds): Builder {
                return QueryGuardSupport::whereHasInOrDenyAll($query, 'residence', 'id', $residenceIds);
            }
        )
            ->when(
                ! empty($provinceFilters['main_road']),
                fn (Builder $query) => $query->whereHas(
                    'residence',
                    fn ($q) => $q->where('main_road', 'LIKE', '%'.$provinceFilters['main_road'].'%')
                )
            )
            ->when(
                ! empty($statusFilters['residence_activation_status_id']),
                fn (Builder $query) => $query->whereHas(
                    'residence',
                    fn ($q) => $q->whereIn('residence_activation_status_id', (array) $statusFilters['residence_activation_status_id'])
                )
            )
            ->when(
                ! empty($statusFilters['status']),
                fn (Builder $query) => $query->whereIn('status', (array) $statusFilters['status'])
            )
            ->when(
                ! empty($unitSearchFilters['unit_number']),
                fn (Builder $query) => $query->where('unit_number', 'LIKE', '%'.$unitSearchFilters['unit_number'].'%')
            )
            ->when(
                ! empty($unitSearchFilters['status']),
                fn (Builder $query) => $query->whereIn('status', (array) $unitSearchFilters['status'])
            );

        return $query;
    }
}
