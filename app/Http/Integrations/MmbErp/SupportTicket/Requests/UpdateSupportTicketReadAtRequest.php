<?php

namespace App\Http\Integrations\MmbErp\SupportTicket\Requests;

use App\Http\Integrations\MmbErp\MmbErpConnector;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class UpdateSupportTicketReadAtRequest extends Request implements HasBody
{
    use HasConnector, HasJsonBody;

    public function __construct(
        protected array $payload,
        protected int $id
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
        return '/api/v1/support-ticket/read-status/'.$this->id;
    }

    public function defaultBody(): array
    {
        return $this->payload;
    }
}
