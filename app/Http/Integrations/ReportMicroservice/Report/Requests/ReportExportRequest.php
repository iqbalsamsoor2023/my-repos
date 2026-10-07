<?php

namespace App\Http\Integrations\ReportMicroservice\Report\Requests;

use App\Http\Integrations\ReportMicroservice\ReportMicroserviceConnector;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class ReportExportRequest extends Request implements HasBody
{
    use HasConnector, HasJsonBody;

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
    protected Method $method = Method::POST;

    /**
     * The endpoint of the request.
     */
    public function resolveEndpoint(): string
    {
        return '/api/v1/web/reports/export';
    }

    public function defaultBody(): array
    {
        return $this->payload;
    }
}
