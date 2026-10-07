<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Nuwave\Lighthouse\Support\Http\Middleware\LogGraphQLQueries;

/**
 * Logs every incoming GraphQL query.
 */
class LogGraphQL extends LogGraphQLQueries
{
    /**
     * @return mixed Any kind of response
     * @return mixed Any kind of response
     */
    public function handle(Request $request, Closure $next)
    {
        Log::channel('graphql-query')->info(
            self::MESSAGE,
            $request->json()->all());

        return $next($request);
    }
}
