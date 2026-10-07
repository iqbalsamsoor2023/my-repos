<?php

namespace App\Actions\UserSgoc;

use App\Enums\User\RoleType;
use App\Http\Integrations\MySgoc\SecurityGuard\Requests\CreateSecurityGuardRequest;
use App\Models\Residence;

class CreateSecurityGuardUserAction
{
    public function execute(Residence $residence)
    {
        $request = new CreateSecurityGuardRequest($this->securityGuardData($residence));
        $response = $request->send();

        return $response->json();
    }

    public function securityGuardData($residence)
    {
        $generate = $residence->subdistrict->code.RoleType::SC->value.str_pad($residence->id, 5, '0', STR_PAD_LEFT);

        $data = [
            'name' => $generate,
            'email' => $generate.RoleType::DOMAIN_MAIL->value,
            'password' => $generate,
            'role' => RoleType::SECURITY_GUARD->value,
            'company_id' => $residence->sgoc_company_id,
        ];

        return $data;
    }
}
