<?php

namespace App\Services;

use App\Actions\SosManagement\CreateSosManagementAction;
use App\Actions\SosManagement\GetSosManagementAction;
use App\Actions\SosManagement\UpdateSosManagementAction;
use App\Http\Integrations\MySgoc\Notification\Requests\SosAlertRequest;
use App\Http\Requests\SosManagement\StoreSosManagementRequest;
use App\Http\Requests\SosManagement\UpdateSosManagementRequest;
use App\Models\SosManagement;
use Illuminate\Http\Request;

class SosManagementService
{
    public function index(Request $request)
    {
        $getSosManagementAction = new GetSosManagementAction;
        $sosManagement = $getSosManagementAction->execute($request);

        return $sosManagement;
    }

    public function create(StoreSosManagementRequest $request)
    {
        $sosManagementAction = new CreateSosManagementAction;

        $sos_management = $sosManagementAction->execute($request);
        $altered_sos_management = collect($sos_management)->merge([
            'created_by_name' => $sos_management->createdBy->name,
            'unit_no' => $sos_management->unit->unit_number,
            'street' => $sos_management->unit->street,
            'floor' => $sos_management->unit->floor,
            'block' => $sos_management->unit->block,
            'sos_type' => $sos_management->user_action_request,
            'guard_user_id' => $sos_management->unit->residence->sgoc_residence_guard_user_id,
        ]);

        $request = new SosAlertRequest($altered_sos_management);
        $request->send();

        return $sos_management;
    }

    public function update(UpdateSosManagementRequest $request, int $id)
    {
        $sos_management = SosManagement::findOrFail($id);
        $sosManagementAction = new UpdateSosManagementAction;
        $sos_management = $sosManagementAction->execute($request, $sos_management);

        return $sos_management;
    }
}
