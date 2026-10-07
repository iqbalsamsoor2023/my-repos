<?php

namespace App\Actions\PreregisterVisitor;

use App\Exceptions\GeneralException;
use App\Http\Requests\PreregisterVisitor\StorePreregisterVisitorRequest;
use App\Models\PreregisterVisitor;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CreatePreregisterVisitorAction
{
    public function execute(StorePreregisterVisitorRequest $request, Visitor $visitor)
    {
        $request->merge([
            'visitor_id' => $visitor->id,
            'visitor_code' => Str::random(20),
        ]);

        $preregister_visitor = $this->createPreregisterVisitor($request);

        return $preregister_visitor;
    }

    private function createPreregisterVisitor(StorePreregisterVisitorRequest $request)
    {
        $preregister_visitor = PreregisterVisitor::create($request->only([
            'visitor_id',
            'visitor_code',
            'arrival_type',
            'vehicle_type',
            'visitor_purpose',
            'vehicle_plate_no',
            'is_multiple_entry',
            'validity_start_date',
            'validity_end_date',
            'unit_id',
            'user_id',
        ]));

        if (! $preregister_visitor) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed creating pre-register vsitor');
        }

        return $preregister_visitor;
    }
}
