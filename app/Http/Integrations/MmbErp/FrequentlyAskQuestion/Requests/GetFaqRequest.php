<?php

namespace App\Http\Integrations\MmbErp\FrequentlyAskQuestion\Requests;

use App\Http\Integrations\MmbErp\MmbErpConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\HasConnector;

class GetFaqRequest extends Request
{
    use HasConnector;

    public function __construct(
        protected array $payload
    ) {}

    /**
     * The connector class.
     */
    protected ?string $connector = MmbErpConnector::class;

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
        return '/api/v1/frequently-ask-questions';
    }

    public function defaultQuery(): array
    {
        return $this->payload;
    }

    public function defaultHeaders(): array
    {
        return [
            'Accept-Language' => request()->header('Accept-Language', 'en'),
        ];
    }
}
