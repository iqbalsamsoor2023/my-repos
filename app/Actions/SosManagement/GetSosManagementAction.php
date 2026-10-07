<?php

namespace App\Actions\SosManagement;

use App\Models\SosManagement;

class GetSosManagementAction
{
    public function execute($request)
    {
        $sosManagement = SosManagement::with('unit', 'unit.residence', 'createdBy');

        if (isset($request->user_id)) {
            $sosManagement = $sosManagement->where('created_by_id', $request->user_id);
        }

        if (isset($request->residence_id)) {
            $sosManagement = $sosManagement->whereHas('unit.residence', function ($query) use ($request) {
                return $query->where('id', $request->residence_id);
            });
        }

        if (isset($request->accepted_by_ids)) {
            $sosManagement = $sosManagement->whereIn('accepted_by_id', $request->accepted_by_ids);
        }

        return $sosManagement->orderBy('id', 'DESC')->paginate(20);
    }
}
