<?php

namespace App\Enums\Residence;

enum BpoSoftwareEnum: string
{
    case SOFTWARE_ACCOUNTING = 'accounting';
    case SOFTWARE_VMS = 'vms';
    case APPS_USER = 'apps_user';

    public function label(): string
    {
        return match ($this) {
            self::SOFTWARE_ACCOUNTING => __('bpo_software.categories.accounting'),
            self::SOFTWARE_VMS => __('bpo_software.categories.vms'),
            self::APPS_USER => __('bpo_software.categories.apps_user'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();
    }
}
