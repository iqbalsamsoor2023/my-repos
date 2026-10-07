<?php

namespace App\Helpers;

use App\Enums\User\RoleType;
use App\Models\Company;
use App\Models\Residence;
use App\Models\User;

class AutomationAccountGenerator
{
    public static function generate(Residence $residence, ?string $role_name = null): array
    {
        $generate = $residence->subdistrict->code.$role_name.str_pad($residence->id, 5, '0', STR_PAD_LEFT);

        return [
            'name' => $generate,
            'email' => $generate.RoleType::DOMAIN_MAIL->value,
        ];
    }

    public static function generatePmoc(Company $company, ?string $role_name = null): array
    {
        $generate = $company->province->code.str_pad($company->id, 3, '0', STR_PAD_LEFT).$role_name;

        return [
            'name' => $generate,
            'email' => $generate.RoleType::DOMAIN_MAIL->value,
        ];
    }

    public static function attachRole(User $user, ?string $role_name = null): User
    {
        return $user->assignRole($role_name);
    }

    public static function manageAccount(?array $data = null): array
    {
        return [
            'country_id' => 1,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['name']),
            'email_verified_at' => date('Y-m-d H:i:s'),
        ];
    }
}
