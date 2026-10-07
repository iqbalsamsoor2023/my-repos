<?php

namespace App\Actions\VisitingArrangement;

use App\Exceptions\GeneralException;
use App\Http\Requests\Visitor\StoreVisitorRequest;
use App\Models\VisitingArrangement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreateVisitingArrangementAction
{
    public function execute(StoreVisitorRequest $request)
    {
        return $this->createVisitingArrangement($request);
    }

    public function createVisitingArrangement(StoreVisitorRequest $request)
    {
        $visiting_arrangement = VisitingArrangement::create($request->only([
            'visitor_log_id',
            'residence_id',
            'unit_id',
            'user_id',
            'status',
            'estamp_by',
        ]));

        if (! $visiting_arrangement) {
            DB::rollBack();
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating visiting arrangements');
        }

        return $visiting_arrangement;
    }
}
