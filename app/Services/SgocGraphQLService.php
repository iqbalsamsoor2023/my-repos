<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Class GraphQLService.
 */
class SgocGraphQLService
{
    public static function execute(string $query, array $request = [])
    {
        // update your local docker url
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(config('mysgoc.url').'/graphql', [
            'query' => $query,
            'variables' => $request,
        ]);

        return $response;
    }
}
