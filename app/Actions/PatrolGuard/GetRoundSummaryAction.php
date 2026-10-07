<?php

namespace App\Actions\PatrolGuard;

use App\Exceptions\GeneralException;
use App\Http\Integrations\MySgoc\PatrolGuard\Requests\GetRoundSummaryRequest;
use Illuminate\Http\JsonResponse;

class GetRoundSummaryAction
{
    public function execute(array $payload): array
    {
        $request = new GetRoundSummaryRequest($payload);
        $response = $request->send();

        if ($response->failed()) {
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed to fetch round summary');
        }

        return $response->json();
    }
}
