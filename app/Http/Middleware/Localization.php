<?php

namespace App\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Localization
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
        /**
         * requests hasHeader is used to check the Accept-Language header from the REST API's
         */
        if ($request->hasHeader('Accept-Language')) {
            $preferred = $request->getPreferredLanguage(['th', 'en']);
            App::setLocale($preferred);
        }

        return $next($request);
    }
}
