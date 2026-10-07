<?php

namespace App\Traits;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Builder;

trait ResidenceWidgetFilters
{
    protected function getFilteredResidenceQuery(): Builder
    {
        $user = auth()->user();

        $query = Residence::query();

        // Restrict for Property Management role similar to ListResidences::getTableQuery
        if ($user->hasRole('Property Management')) {
            $residence = get_residence_by_property_management($user->id);
            if ($residence) {
                $query->whereId($residence->id);
            }
        }

        return $this->applyTableFiltersToQuery($query);
    }

    protected function applyTableFiltersToQuery(Builder $query): Builder
    {
        $filters = $this->tableFilters ?? [];

        // Mooban filters
        if (! empty($filters['mooban']['mooban_type'] ?? null)) {
            $query->whereIn('mooban_type', (array) $filters['mooban']['mooban_type']);
        }

        if (! empty($filters['mooban']['sub_type'] ?? null)) {
            $query->whereIn('sub_type', (array) $filters['mooban']['sub_type']);
        }

        if (! empty($filters['mooban']['name'] ?? null)) {
            $query->whereId($filters['mooban']['name']);
        }

        // Province / district / subdistrict / main_road
        if (! empty($filters['province_filters']['province'] ?? null)) {
            $provinceIds = (array) $filters['province_filters']['province'];
            $query->whereHas('subdistrict.district.province', function ($q) use ($provinceIds) {
                $q->whereIn('id', $provinceIds);
            });
        }

        if (! empty($filters['province_filters']['district'] ?? null)) {
            $districtIds = (array) $filters['province_filters']['district'];
            $query->whereHas('subdistrict.district', function ($q) use ($districtIds) {
                $q->whereIn('id', $districtIds);
            });
        }

        if (! empty($filters['province_filters']['subdistrict'] ?? null)) {
            $subdistrictIds = (array) $filters['province_filters']['subdistrict'];
            $query->whereHas('subdistrict', function ($q) use ($subdistrictIds) {
                $q->whereIn('id', $subdistrictIds);
            });
        }

        if (! empty($filters['province_filters']['main_road'] ?? null)) {
            $query->where('main_road', 'LIKE', '%'.$filters['province_filters']['main_road'].'%');
        }

        // Status
        if (! empty($filters['status_filters']['residence_activation_status_id'] ?? null)) {
            $query->whereIn('residence_activation_status_id', (array) $filters['status_filters']['residence_activation_status_id']);
        }

        // Date filters
        if (! empty($filters['date_filters']['created_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['date_filters']['created_from']);
        }
        if (! empty($filters['date_filters']['created_until'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['date_filters']['created_until']);
        }
        if (! empty($filters['date_filters']['updated_from'] ?? null)) {
            $query->whereDate('updated_at', '>=', $filters['date_filters']['updated_from']);
        }
        if (! empty($filters['date_filters']['updated_until'] ?? null)) {
            $query->whereDate('updated_at', '<=', $filters['date_filters']['updated_until']);
        }

        // Companies
        if (! empty($filters['companies']['developer_id'] ?? null)) {
            $query->where('developer_id', $filters['companies']['developer_id']);
        }
        if (! empty($filters['companies']['property_management_id'] ?? null)) {
            $query->where('property_management_id', $filters['companies']['property_management_id']);
        }
        if (! empty($filters['companies']['sgoc_company_id'] ?? null)) {
            $query->where('sgoc_company_id', $filters['companies']['sgoc_company_id']);
        }

        // Residence infrastructure filters
        if (! empty($filters['residence_infrastructure_filters']['residence_age_category'] ?? null)) {
            $value = $filters['residence_infrastructure_filters']['residence_age_category'];
            $currentYear = now()->year;

            if ($value === 'Not Yet Set') {
                $query->whereNull('completion_year');
            } else {
                [$min, $max] = match ($value) {
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
                    '50+' => [51, PHP_INT_MAX],
                    default => [0, PHP_INT_MAX],
                };

                $minCompletionYear = $currentYear - $max;
                $maxCompletionYear = $currentYear - $min;

                $query->where('completion_year', '<=', $maxCompletionYear);
                if ($min !== 51) {
                    $query->where('completion_year', '>', $minCompletionYear);
                }
            }
        }

        if (isset($filters['residence_infrastructure_filters']['guard_house_entry_number'])) {
            $value = $filters['residence_infrastructure_filters']['guard_house_entry_number'];
            if ($value === 'Not Yet Set') {
                $query->whereNull('guard_house_entry_number');
            } else {
                $query->where('guard_house_entry_number', $value);
            }
        }

        if (! empty($filters['residence_infrastructure_filters']['guard_house_lane_type'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['guard_house_lane_type'];
            if ($val === 'Not Yet Set') {
                $query->whereNull('guard_house_lane_type');
            } else {
                $query->where('guard_house_lane_type', $val);
            }
        }

        if (isset($filters['residence_infrastructure_filters']['guard_house_type'])) {
            $val = $filters['residence_infrastructure_filters']['guard_house_type'];
            if ($val === 'Not Yet Set') {
                $query->whereNull('has_roof');
            } else {
                $query->where('has_roof', (bool) $val);
            }
        }

        if (! empty($filters['residence_infrastructure_filters']['entrance_barrier_type'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['entrance_barrier_type'];
            if ($val === 'Not Yet Set') {
                $query->whereNull('entrance_barrier_type');
            } else {
                $query->where('entrance_barrier_type', $val);
            }
        }

        if (! empty($filters['residence_infrastructure_filters']['internet_provider'] ?? null)) {
            $val = $filters['residence_infrastructure_filters']['internet_provider'];
            if ($val === 'Not Yet Set') {
                $query->whereNull('internet_provider');
            } else {
                $query->whereJsonContains('internet_provider_id', (string) $val);
            }
        }

        if (isset($filters['residence_infrastructure_filters']['has_cctv'])) {
            $query->where('has_cctv', filter_var($filters['residence_infrastructure_filters']['has_cctv'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }
}
