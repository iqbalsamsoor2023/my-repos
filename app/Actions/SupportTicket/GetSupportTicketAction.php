<?php

namespace App\Actions\SupportTicket;

use App\Exceptions\GeneralException;
use App\Http\Integrations\MmbErp\SupportTicket\Requests\GetSupportTicketRequest;
use App\Http\Resources\SupportTicket\SupportTicketListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSupportTicketAction
{
    public function execute(Request $request)
    {
        $request = new GetSupportTicketRequest($request->all());
        $response = $request->send();

        if (isset($response->json()['data'])) {
            return SupportTicketListResource::collection(collect($response->json()['data']['data']));
        }

        throw new GeneralException(JsonResponse::HTTP_BAD_REQUEST, 'Failed');
    }
}
