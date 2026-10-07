<?php

namespace App\Actions\Visitor;

use App\Exceptions\GeneralException;
use App\Http\Requests\Visitor\UpdateVisitorRequest;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\JsonResponse;

class UpdateVisitorLogAction
{
    public function execute(UpdateVisitorRequest $request, $visitor_log)
    {
        $this->updateVisitorLog($request, $visitor_log);
    }

    public function updateVisitorLog(UpdateVisitorRequest $request, $visitor_log)
    {
        if (! $visitor_log) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating visitor');
        }

        $payload = [
            'leave_time' => Carbon::now(new DateTimeZone('Asia/Bangkok'))->toDateTimeString(),
        ];

        if ($request->filled('visitor_card_id')) {
            $payload['visitor_card_id'] = $request->visitor_card_id;
        }

        if (! $visitor_log->update($payload)) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed updating visitor');
        }
    }
}
