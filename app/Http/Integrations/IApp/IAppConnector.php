<?php

namespace App\Http\Integrations\IApp;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class IAppConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return config('services.iapp.url');
    }

    /**
     * The headers that will be applied to every request.
     *
     * @return string[]
     */
    public function defaultHeaders(): array
    {
        return [
            'apikey' => config('services.iapp.api_key'),
        ];
    }

    /**
     * The config options that will be applied to every request.
     *
     * @return string[]
     */
    public function defaultConfig(): array
    {
        return [
            'follow_redirects' => true,
        ];
    }
}
