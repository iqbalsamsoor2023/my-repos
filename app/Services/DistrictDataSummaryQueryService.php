<?php

namespace App\Services;

use App\Enums\Residence\MoobanType;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Builder;

class DistrictDataSummaryQueryService
{
    public static function build(array $provinceIds = [], array $districtIds = []): Builder
    {
        return Residence::query()
            ->where('residences.mooban_type', MoobanType::PUBLIC->value)
            ->select('residences.*')
            ->leftJoin('residence_stats_view as rsv', 'rsv.residence_id', '=', 'residences.id')
            ->leftJoin('residence_activation_statuses as ras', 'ras.id', '=', 'residences.residence_activation_status_id')
            ->leftJoin(config('database.connections.mmbcnerp.database').'.thailand_sub_districts as sd', 'sd.id', '=', 'residences.subdistrict_id')
            ->leftJoin(config('database.connections.mmbcnerp.database').'.thailand_districts as td', 'td.id', '=', 'sd.district_id')
            ->leftJoin(config('database.connections.mmbcnerp.database').'.thailand_provinces as tp', 'tp.id', '=', 'td.province_id')
            ->leftJoin(config('database.connections.mmbcnerp.database').'.cdp_companies as pm', 'pm.id', '=', 'residences.property_management_id')
            ->leftJoin(config('database.connections.mmbcnerp.database').'.business_entities as be', 'be.id', '=', 'pm.business_entity_id')
            ->leftJoin('users as pmu', 'pmu.id', '=', 'residences.property_management_user_id')
            ->addSelect([
                'rsv.distinct_user_count',
                'rsv.sign_up_percentage',
                'rsv.units_count',
                'rsv.service_duration_latest',
                'ras.status as activation_status_name',
                'td.id as district_id',
                'tp.id as province_id',
                'td.name_in_english as district_name',
                'td.name_in_thai as district_name_th',
                'be.name as pm_company_name',
                'pmu.name as pm_user_name',
            ])
            ->withMax([
                'subscriptionExpires as sg_expiry_date' => fn (Builder $query) => $query->where('type', 'Sgoc'),
            ], 'expiry_date')
            ->when(! empty($provinceIds), fn (Builder $query) => $query->whereIn('tp.id', $provinceIds))
            ->when(! empty($districtIds), fn (Builder $query) => $query->whereIn('td.id', $districtIds));
    }
}
