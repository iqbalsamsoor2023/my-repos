<?php

namespace App\Http\Integrations\MmbErp;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MmbErpConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return (string) config('services.mmbcnerp.url');
    }

    /**
     * The headers that will be applied to every request.
     *
     * @return string[]
     */
    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.config('services.mmbcnerp.api_key') ?? null,
            'Accept-Language' => request()->header('Accept-Language', 'en'),
        ];
    }
}
