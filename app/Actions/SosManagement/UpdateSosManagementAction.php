<?php

namespace App\Actions\SosManagement;

use App\Exceptions\GeneralException;
use App\Http\Requests\SosManagement\UpdateSosManagementRequest;
use App\Models\SosManagement;
use Illuminate\Http\JsonResponse;

class UpdateSosManagementAction
{
    public function execute(UpdateSosManagementRequest $request, SosManagement $sos_management)
    {
        $sos_management = $this->updateSosManagement($request, $sos_management);

        return $sos_management;
    }

    private function updateSosManagement(UpdateSosManagementRequest $request, SosManagement $sos_management)
    {
        $sos_management = $sos_management->update($request->only([
            'accepted_by_id',
            'user_action_request',
            'status',
            'remark',
        ]));

        if (! $sos_management) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating pet');
        }

        return $sos_management;
    }
}
