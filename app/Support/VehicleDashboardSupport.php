<?php

namespace App\Support;

use App\Models\User;
use App\Policies\VehiclePolicy;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class VehicleDashboardSupport
{
    public static function applyReadModelScope(QueryBuilder $query, ?User $user): bool
    {
        if (! VehiclePolicy::hasDashboardAccess($user)) {
            return false;
        }

        if (VehiclePolicy::isGlobalAdmin($user)) {
            return true;
        }

        if (VehiclePolicy::isPropertyManager($user)) {
            $residenceId = DB::table('residences')
                ->where('property_management_user_id', $user->id)
                ->value('id');

            if (! $residenceId) {
                QueryGuardSupport::denyAll($query);

                return true;
            }

            $query->where('residence_id', (int) $residenceId);

            return true;
        }

        if (VehiclePolicy::isOperationCenter($user)) {
            $residenceIds = array_map(
                'intval',
                array_filter((array) get_residence_by_property_management_operation_center($user->id))
            );

            QueryGuardSupport::whereInOrDenyAll($query, 'residence_id', $residenceIds);

            return true;
        }

        return false;
    }

    public static function vehicleIdScopeSubquery(?User $user): QueryBuilder
    {
        $query = DB::table('vehicle_stats_view')->select('vehicle_id');

        if (! static::applyReadModelScope($query, $user)) {
            QueryGuardSupport::denyAll($query);
        }

        return $query;
    }

    public static function normalizedFilters(array $filters): array
    {
        return Arr::sortRecursive($filters);
    }

    public static function flattenFilters(array $filters): array
    {
        return collect(Arr::dot($filters))
            ->filter(static fn (mixed $value): bool => filled($value))
            ->values()
            ->all();
    }
}
