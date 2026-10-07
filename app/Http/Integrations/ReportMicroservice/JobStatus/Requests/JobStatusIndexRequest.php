<?php

namespace App\Http\Integrations\ReportMicroservice\JobStatus\Requests;

use App\Http\Integrations\ReportMicroservice\ReportMicroserviceConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\HasConnector;

class JobStatusIndexRequest extends Request
{
    use HasConnector;

    public function __construct(
        protected array $payload
    ) {}

    /**
     * The connector class.
     */
    protected ?string $connector = ReportMicroserviceConnector::class;

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
        return '/api/v1/web/job-statuses';
    }

    public function defaultQuery(): array
    {
        return $this->payload;
    }
}
