<?php

namespace App\Services;

use App\Actions\VisitingArrangement\GetVisitingArrangementAction;
use App\Actions\VisitingArrangement\UpdateVisitingArrangementAction;
use App\Http\Requests\VisitingArrangement\UpdateVisitingArrangementRequest;
use App\Models\VisitingArrangement;

class VisitingArrangementService
{
    public function index($request)
    {
        $visitingArrangementAction = new GetVisitingArrangementAction;

        return $visitingArrangementAction->execute($request);
    }

    public function update(UpdateVisitingArrangementRequest $request, int $id)
    {
        $visiting_arrangement = VisitingArrangement::whereId($id)->whereNull('estamp_by');
        $visitingArrangementAction = new UpdateVisitingArrangementAction;

        return $visitingArrangementAction->execute($request, $visiting_arrangement);
    }

    public function updateByVisitor(UpdateVisitingArrangementRequest $request, int $id)
    {
        if (isset($request->visiting_arrangement_id)) {
            $base = VisitingArrangement::where('id', $request->visiting_arrangement_id)->firstOrFail();
    
            $query = VisitingArrangement::where('visitor_log_id', $base->visitor_log_id)
                ->where('unit_id', $request->unit_id)
                ->whereNull('estamp_by');
        } else {
            $query = VisitingArrangement::where('visitor_log_id', $id)
                ->whereNull('estamp_by');
        }
    
        if (! is_null($request->unit_id)) {
            $query->where('unit_id', $request->unit_id);
        }
    
        $visitingArrangementAction = new UpdateVisitingArrangementAction;
    
        return $visitingArrangementAction->execute($request, $query);
    }
}
