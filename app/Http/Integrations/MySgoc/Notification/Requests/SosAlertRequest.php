<?php

namespace App\Http\Integrations\MySgoc\Notification\Requests;

use App\Http\Integrations\MySgoc\MySgocConnector;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class SosAlertRequest extends Request implements HasBody
{
    use HasConnector, HasJsonBody;

    public $data;

    public function __construct($request)
    {
        $this->data = $request;
    }

    /**
     * The connector class.
     */
    protected ?string $connector = MySgocConnector::class;

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
        return '/api/v1/notifications/sos-alarm';
    }

    public function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
