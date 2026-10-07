<?php

namespace App\Actions\PatrolGuard;

use App\Exceptions\GeneralException;
use App\Http\Integrations\MySgoc\PatrolGuard\Requests\GetCheckpointSummaryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GetCheckpointSummaryAction
{
    public function execute(array $payload): array
    {
        $request = new GetCheckpointSummaryRequest($payload);
        $response = $request->send();

        if ($response->failed()) {
            Log::error('Failed to fetch checkpoint summary', [
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed to fetch checkpoint summary');
        }

        return $response->json();
    }
}
