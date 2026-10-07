<?php

namespace App\Http\Integrations\ReportMicroservice;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class ReportMicroserviceConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return (string) config('services.rm.url');
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
            'Authorization' => 'Bearer '.config('services.rm.api_key') ?? null,
        ];
    }
}
