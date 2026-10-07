<?php

namespace App\Actions\UserSgoc;

use App\Http\Integrations\MySgoc\SecurityGuard\Requests\UpdateSecurityGuardUserRequest;

class UpdateSecurityGuardUserAction
{
    public function execute($residence, int $id)
    {
        $request = new UpdateSecurityGuardUserRequest($this->supportTicketData($residence), $id);
        $response = $request->send();

        return $response->json();
    }

    public function supportTicketData($residence)
    {
        return [
            '_method' => 'PUT',
            'company_id' => $residence->sgoc_company_id,
        ];
    }
}
