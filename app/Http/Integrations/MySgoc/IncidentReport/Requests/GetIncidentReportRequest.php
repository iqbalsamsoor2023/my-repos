<?php

namespace App\Http\Integrations\MySgoc\IncidentReport\Requests;

use App\Http\Integrations\MySgoc\MySgocConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\HasConnector;

class GetIncidentReportRequest extends Request
{
    use HasConnector;

    public function __construct(
        protected array $payload
    ) {}

    /**
     * The connector class.
     */
    protected ?string $connector = MySgocConnector::class;

    /**
     * The HTTP verb the request will use.
     *
     * @var string|null
     */
    protected Method $method = Method::GET;

    /**
     * The endpoint of the request.
     */
    public function resolveEndpoint(): string
    {
        return '/api/v1/incident-reports';
    }

    public function defaultQuery(): array
    {
        return $this->payload;
    }
}
