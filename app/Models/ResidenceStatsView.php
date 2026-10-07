<?php

namespace App\Models;

use App\Enums\Residence\MoobanType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceStatsView extends Model
{
    protected $table = 'residence_stats_view';

    protected $primaryKey = 'residence_id';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'internet_provider_id' => 'array',
            'juristic_details' => 'array',
            'bpo_software_suppliers' => 'array',
            'person_in_charges' => 'array',
            'has_cctv' => 'boolean',
            'has_roof' => 'boolean',
            'sign_up_percentage' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function publicMoobans(Builder $query): void
    {
        $query->where('mooban_type', MoobanType::PUBLIC->value);
    }

    #[Scope]
    protected function inProvinces(Builder $query, array $provinceIds): void
    {
        if (! empty($provinceIds)) {
            $query->whereIn('province_id', $provinceIds);
        }
    }

    #[Scope]
    protected function inDistricts(Builder $query, array $districtIds): void
    {
        if (! empty($districtIds)) {
            $query->whereIn('district_id', $districtIds);
        }
    }

    #[Scope]
    protected function withSubTypes(Builder $query, array $subTypes): void
    {
        if (! empty($subTypes)) {
            $query->whereIn('sub_type', $subTypes);
        }
    }

    #[Scope]
    protected function withActivationStatuses(Builder $query, array $activationStatusIds): void
    {
        if (! empty($activationStatusIds)) {
            $query->whereIn('residence_activation_status_id', $activationStatusIds);
        }
    }

    #[Scope]
    protected function withPropertyManagementTypes(Builder $query, array $propertyManagementTypes): void
    {
        if (! empty($propertyManagementTypes)) {
            $query->whereIn('property_management_type', $propertyManagementTypes);
        }
    }

    /** @return array{total_units: int, total_residents: int} */
    public static function aggregateTotals(
        array $provinceIds = [],
        array $districtIds = [],
        array $subTypes = [],
        array $activationStatusIds = [],
        array $propertyManagementTypes = [],
    ): array {
        $row = static::query()
            ->publicMoobans()
            ->inProvinces($provinceIds)
            ->inDistricts($districtIds)
            ->withSubTypes($subTypes)
            ->withActivationStatuses($activationStatusIds)
            ->withPropertyManagementTypes($propertyManagementTypes)
            ->selectRaw('SUM(units_count) as total_units, SUM(distinct_user_count) as total_residents')
            ->first();

        return [
            'total_units' => (int) ($row->total_units ?? 0),
            'total_residents' => (int) ($row->total_residents ?? 0),
        ];
    }

    /**
     * Get the original residence model (for edit links, etc.)
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class, 'residence_id');
    }

    public function activationStatus(): BelongsTo
    {
        return $this->belongsTo(ResidenceActivationStatus::class, 'residence_activation_status_id');
    }

    /**
     * Alias for district_name_en (backward compat with DistrictDataSummary).
     */
    public function getDistrictNameAttribute(): ?string
    {
        return $this->attributes['district_name_en'] ?? null;
    }

    /**
     * Alias for district_name_th (backward compat with DistrictDataSummary).
     */
    public function getDistrictNameThAttribute(): ?string
    {
        return $this->attributes['district_name_th'] ?? null;
    }
}
