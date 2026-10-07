<?php

namespace App\Support;

use App\Models\InsuranceCompany;

class VehicleInsuranceSupport
{
    public static function displayName(?InsuranceCompany $insuranceCompany): string
    {
        return $insuranceCompany?->name
            ?? $insuranceCompany?->name_th
            ?? '-';
    }

    public static function secondaryName(?InsuranceCompany $insuranceCompany): ?string
    {
        if (! filled($insuranceCompany?->name) || ! filled($insuranceCompany?->name_th)) {
            return null;
        }

        return $insuranceCompany->name !== $insuranceCompany->name_th
            ? $insuranceCompany->name_th
            : null;
    }
}
