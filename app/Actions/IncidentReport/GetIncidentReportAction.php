<?php

namespace App\Actions\IncidentReport;

use App\Exceptions\GeneralException;
use App\Http\Integrations\MySgoc\IncidentReport\Requests\GetIncidentReportRequest;
use App\Http\Resources\IncidentReport\IncidentReportPaginatedResource;
use Illuminate\Http\JsonResponse;

class GetIncidentReportAction
{
    public function execute($request)
    {
        $request = new GetIncidentReportRequest($request->all());
        $response = $request->send();

        if ($response->json()['http_code'] === JsonResponse::HTTP_OK) {
            return new IncidentReportPaginatedResource($response->json()['data']);
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed');
    }
}
