<?php

namespace App\Helpers;

use Exception;
use App\Models\Maintenance;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;

class MaintenanceClaimNoGenerator
{
    public static function generate(Maintenance $maintenance): string
    {
        $residence_id = str_pad($maintenance->maintainable->residence_id, 5, '0', STR_PAD_LEFT);

        $createdDate = $maintenance->created_at->format('Y-m-d');
        $ymd = $maintenance->created_at->format('ymd');

        $counter = 0;
        $maxAttempts = 1000;

        do {
            $number = Maintenance::whereHasMorph('maintainable', [Unit::class], function ($query) use ($maintenance) {
                $query->where('residence_id', $maintenance->maintainable->residence_id);
            })
                ->whereDate('created_at', $createdDate)
                ->count() + $counter;

            $work_order_number = str_pad($number, 3, '0', STR_PAD_LEFT);
            $claim_number = 'PA'.$residence_id.'-'.$ymd.'-'.$work_order_number;

            $exists = Maintenance::where('maintainable_claim_number', $claim_number)->exists();

            $counter++;
        } while ($exists && $counter < $maxAttempts);

        if ($counter >= $maxAttempts) {
            throw new Exception("Failed to generate unique private claim number after {$maxAttempts} attempts.");
        }

        return $claim_number;
    }

    public static function generatePublicClaimNo(Maintenance $maintenance): string
    {
        $maintainable = $maintenance->maintainable;

        if ($maintainable instanceof ResidenceAmenity) {
            $residenceId = $maintainable->residence_id;
        } elseif ($maintainable instanceof ResidenceAmenityOption) {
            $residenceId = $maintainable->residenceAmenity?->residence_id;
        } else {
            throw new Exception("Unsupported maintainable type or residence_id not found for Maintenance ID {$maintenance->id}");
        }

        if (!$residenceId) {
            throw new Exception("Residence ID could not be resolved for Maintenance ID {$maintenance->id}");
        }

        $residence_id = $residenceId ? str_pad($residenceId, 5, '0', STR_PAD_LEFT) : null;

        $createdDate = $maintenance->created_at->format('Y-m-d');
        $ymd = $maintenance->created_at->format('ymd');

        $counter = 0;
        $maxAttempts = 1000;

        $types = [ResidenceAmenity::class, ResidenceAmenityOption::class];

        do {
            $number = Maintenance::whereDate('created_at', $createdDate)
                ->whereHasMorph('maintainable', $types, function ($query, $type) use ($residenceId) {
                    if ($type === ResidenceAmenity::class) {
                        $query->where('residence_id', $residenceId);
                    } elseif ($type === ResidenceAmenityOption::class) {
                        $query->whereHas('residenceAmenity', function ($subQuery) use ($residenceId) {
                            $subQuery->where('residence_id', $residenceId);
                        });
                    }
                })
                ->count() + $counter;

            $work_order_number = str_pad($number, 3, '0', STR_PAD_LEFT);
            $claim_number = 'CA'.$residence_id.'-'.$ymd.'-'.$work_order_number;

            $exists = Maintenance::where('maintainable_claim_number', $claim_number)->exists();

            $counter++;
        } while ($exists && $counter < $maxAttempts);

        if ($counter >= $maxAttempts) {
            throw new Exception("Failed to generate unique public claim number after {$maxAttempts} attempts.");
        }

        return $claim_number;
    }
}
