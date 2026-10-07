<?php

namespace App\Http\Integrations\MySgoc\PatrolGuard\Requests;

use App\Http\Integrations\MySgoc\MySgocConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\HasConnector;

class GetRoundSummaryRequest extends Request
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
        return '/api/v1/internal/patrol-guard/round-summary';
    }

    public function defaultQuery(): array
    {
        return $this->payload;
    }
}
