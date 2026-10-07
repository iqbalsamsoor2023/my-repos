<?php

namespace App\Http\Integrations\MySgoc;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MySgocConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return (string) config('services.mysgoc.url');
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
            'Authorization' => 'Bearer '.config('services.mysgoc.api_key') ?? null,
        ];
    }
}
