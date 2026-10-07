<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceCategory;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class GetMaintenanceAction
{
    public function execute($request)
    {
        $maintenance = Maintenance::with('maintainable', 'maintenanceProgressions', 'reportedBy', 'media');

        $hasCategory = $request->filled('claim_category_id');
        $hasType = $request->filled('maintainable_type');

        // New version using claim_category_id
        if ($hasCategory) {
            $claimCategoryId = (int) $request->input('claim_category_id');

            $types = match ($claimCategoryId) {
                MaintenanceCategory::PUBLIC->value => [
                    ResidenceAmenity::class,
                    ResidenceAmenityOption::class,
                ],
                MaintenanceCategory::PRIVATE->value => [
                    Unit::class,
                ],
                default => [
                    ResidenceAmenity::class,
                    ResidenceAmenityOption::class,
                    Unit::class,
                ],
            };
        } elseif ($hasType) { // Old version: use maintainable_type only
            $requestedTypes = Arr::wrap($request->input('maintainable_type'));

            // To support backward compatibility MMB V2
            $types = [];
            foreach ($requestedTypes as $type) {
                if ($type === 'App\Models\ClaimableItem') {
                    $types[] = ResidenceAmenity::class;
                    $types[] = ResidenceAmenityOption::class;
                } else {
                    $types[] = $type;
                }
            }

            $types = array_unique($types);
        }

        // Apply to query if types are set
        if (! empty($types)) {
            $maintenance = $maintenance->whereIn('maintainable_type', $types);
        }

        if ($request->has('maintainable_id')) {
            $maintenance = $maintenance->where('maintainable_id', $request->maintainable_id);
        }

        if ($request->has('reported_by')) {
            $maintenance = $maintenance->where('reported_by', $request->reported_by);
        }

        if ($request->has('residence_id')) {
            $residenceId = $request->residence_id;

            $maintenance = $maintenance->whereHasMorph(
                'maintainable',
                $types,
                function (Builder $query, string $type) use ($residenceId) {
                    if ($type === ResidenceAmenityOption::class) {
                        $query->whereHas('residenceAmenity', function ($q) use ($residenceId) {
                            $q->where('residence_id', $residenceId);
                        });
                    } else {
                        $query->where('residence_id', $residenceId);
                    }
                }
            );
        } else {
            $maintenance = $maintenance->whereHasMorph('maintainable', $types);
        }

        return $maintenance->orderByDesc('id')->paginate(20);
    }
}
