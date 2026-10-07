<?php

namespace App\Support;

use App\Enums\Residence\Features;
use App\Enums\User\RoleType;
use Illuminate\Support\Str;

class ResidenceFeatureSupport
{
    /**
     * @return array<string, int>
     */
    public static function formKeyToFeatureMap(): array
    {
        return [
            strtolower(Features::INBOX->name) => Features::INBOX->value,
            strtolower(Features::VISITOR->name) => Features::VISITOR->value,
            strtolower(Features::CLAIM->name) => Features::CLAIM->value,
            strtolower(Features::PARCEL->name) => Features::PARCEL->value,
            strtolower(Features::BOOKING->name) => Features::BOOKING->value,
            strtolower(Features::DEVELOPER->name) => Features::DEVELOPER->value,
            strtolower(Features::CONTACT->name) => Features::CONTACT->value,
            strtolower(Features::BILLING->name) => Features::BILLING->value,
            Str::camel(strtolower(Features::PARKING_FEE_BASIC->name)) => Features::PARKING_FEE_BASIC->value,
            Str::camel(strtolower(Features::PARKING_FEE_PRO->name)) => Features::PARKING_FEE_PRO->value,
            Str::camel(strtolower(Features::SALES_MANAGEMENT->name)) => Features::SALES_MANAGEMENT->value,
            Str::camel(strtolower(Features::RECEPTION_MANAGEMENT->name)) => Features::RECEPTION_MANAGEMENT->value,
            Str::camel(strtolower(Features::RESALE_AND_TENANCY->name)) => Features::RESALE_AND_TENANCY->value,
            Str::camel(strtolower(Features::FACILITIES_MANAGEMENT->name)) => Features::FACILITIES_MANAGEMENT->value,
            Str::camel(strtolower(Features::PROPERTY_MANAGEMENT->name)) => Features::PROPERTY_MANAGEMENT->value,
            Str::camel(strtolower(Features::SECURITY_MANAGEMENT->name)) => Features::SECURITY_MANAGEMENT->value,
            Str::camel(strtolower(Features::ACCOUNTING_MANAGEMENT->name)) => Features::ACCOUNTING_MANAGEMENT->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function featureIdToFormKeyMap(): array
    {
        return array_flip(self::formKeyToFeatureMap());
    }

    /**
     * @return array<int>
     */
    public static function accountManagedFeatureIds(): array
    {
        return [
            Features::SALES_MANAGEMENT->value,
            Features::RECEPTION_MANAGEMENT->value,
            Features::RESALE_AND_TENANCY->value,
            Features::FACILITIES_MANAGEMENT->value,
            Features::PROPERTY_MANAGEMENT->value,
            Features::SECURITY_MANAGEMENT->value,
            Features::ACCOUNTING_MANAGEMENT->value,
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function accountRoleMap(): array
    {
        return [
            Features::SALES_MANAGEMENT->value => [RoleType::SM->value, RoleType::SALES_MANAGEMENT->value],
            Features::RECEPTION_MANAGEMENT->value => [RoleType::RC->value, RoleType::RECEPTIONIST->value],
            Features::RESALE_AND_TENANCY->value => [RoleType::RTM->value, RoleType::RESALES_AND_TENANCY_MANAGEMENT->value],
            Features::PROPERTY_MANAGEMENT->value => [RoleType::PM->value, RoleType::PROPERTY_MANAGEMENT->value],
            Features::ACCOUNTING_MANAGEMENT->value => [RoleType::AC->value, RoleType::ACCOUNTANT->value],
            Features::SECURITY_MANAGEMENT->value => [RoleType::SC->value, RoleType::SECURITY_GUARD->value],
        ];
    }
}
