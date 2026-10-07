<?php

namespace App\Http\Integrations\MmbErp\SupportTicket\Requests;

use App\Http\Integrations\MmbErp\MmbErpConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\HasConnector;

class GetSupportTicketCommentRequest extends Request
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
        return '/api/v1/comments';
    }

    public function defaultQuery(): array
    {
        return $this->payload;
    }
}
