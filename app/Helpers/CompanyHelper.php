<?php

namespace App\Helpers;

use App\Enums\Company\CompanyTypeEnum;
use App\Models\Erp\CdpCompany;

class CompanyHelper
{
    /**
     * Get company options based on category.
     */
    public static function getCompanyOptions(CompanyTypeEnum $companyType): array
    {
        return CdpCompany::query()
            ->whereNull('deleted_at')
            ->whereHas('businessEntity', function ($query) use ($companyType) {
                $query->where('business_category_id', $companyType->value);
            })
            ->with(['businessEntity:id,name,name_th'])
            ->get(['id', 'business_entity_id'])
            ->mapWithKeys(function ($company) {
                $name = $company->businessEntity?->name ?? '';
                $nameTh = $company->businessEntity?->name_th ?? '';

                return [$company->id => "{$name} ({$nameTh})"];
            })
            ->toArray();
    }
}
