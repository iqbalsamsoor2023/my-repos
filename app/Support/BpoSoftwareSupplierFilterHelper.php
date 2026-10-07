<?php

namespace App\Support;

use App\Models\BpoSoftwareSupplier;
use Illuminate\Database\Eloquent\Builder;

class BpoSoftwareSupplierFilterHelper
{
    public static function applyJsonFilter(Builder $query, string $jsonKey, array $values): Builder
    {
        $ids = self::normalizeIds($values);

        if ($ids === []) {
            return $query;
        }

        return $query->where(function (Builder $softwareQuery) use ($jsonKey, $ids) {
            foreach ($ids as $id) {
                $softwareQuery->orWhereJsonContains("residences.bpo_software_suppliers->{$jsonKey}", $id);
            }
        });
    }

    public static function applyNameSearch(Builder $query, string $jsonKey, string $search): Builder
    {
        $supplierIds = BpoSoftwareSupplier::query()
            ->where('name', 'like', "%{$search}%")
            ->orWhere('name_th', 'like', "%{$search}%")
            ->pluck('id')
            ->all();

        if ($supplierIds === []) {
            return QueryGuardSupport::denyAll($query);
        }

        return self::applyJsonFilter($query, $jsonKey, $supplierIds);
    }

    public static function normalizeIds(array $values): array
    {
        return array_values(array_map('intval', array_filter($values, static fn ($value) => $value !== null && $value !== '')));
    }
}
