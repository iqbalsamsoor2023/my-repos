<?php

namespace App\Http\Integrations\MmbErp;

use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasMultipartBody;
use Saloon\Traits\Request\HasConnector;

class GraphQLRequest extends Request implements HasBody
{
    use HasConnector, HasMultipartBody;

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
    protected Method $method = Method::POST;

    /**
     * The endpoint of the request.
     */
    public function resolveEndpoint(): string
    {
        return '/graphQL';
    }

    public function defaultBody(): array
    {
        foreach ($this->payload as $data) {
            $results[] = new MultipartValue(
                name: data_get($data, 'name', null),
                value: data_get($data, 'value', null),
                filename: data_get($data, 'filename', null));
        }

        return $results;
    }
}
