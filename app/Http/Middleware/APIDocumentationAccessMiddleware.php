<?php

namespace App\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Closure;
use Illuminate\Http\Request;

class APIDocumentationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request):((Response|RedirectResponse)) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! in_array($request->ip(), explode(',', env('IP_WHITELISTS')) ?? [])) {
            abort(403, 'Unauthorized');
        }

        return $next($request);

        return $next($request);
    }
}
