<?php

namespace App\Actions\SosManagement;

use App\Enums\SosManagement\Status;
use App\Exceptions\GeneralException;
use App\Http\Requests\SosManagement\StoreSosManagementRequest;
use App\Models\SosManagement;
use Illuminate\Http\JsonResponse;

class CreateSosManagementAction
{
    public function execute(StoreSosManagementRequest $request)
    {
        $sos_management = $this->createSosManagement($request);

        return $sos_management;
    }

    private function createSosManagement(StoreSosManagementRequest $request)
    {
        $request->merge([
            'status' => Status::PENDING->value,
        ]);
        $sos_management = SosManagement::create($request->only([
            'created_by_id',
            'user_action_request',
            'unit_id',
            'longitude',
            'latitude',
            'status',
        ]));

        if (! $sos_management) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating sos management');
        }

        return $sos_management;
    }
}
