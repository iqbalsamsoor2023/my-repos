<?php

namespace App\Actions\VisitingArrangement;

use App\Http\Requests\VisitingArrangement\UpdateVisitingArrangementRequest;

class UpdateVisitingArrangementAction
{
    public function execute(UpdateVisitingArrangementRequest $request, $query)
    {
        $updated = $query->update($request->only([
            'status',
            'estamp_by',
            'feedback_remark',
            'estamp_by_type',
        ]));

        return $updated;
    }
}
