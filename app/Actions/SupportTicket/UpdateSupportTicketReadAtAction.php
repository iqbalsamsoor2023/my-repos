<?php

namespace App\Actions\SupportTicket;

use App\Http\Integrations\MmbErp\SupportTicket\Requests\UpdateSupportTicketReadAtRequest;
use Illuminate\Http\Request;

class UpdateSupportTicketReadAtAction
{
    public function execute(Request $request, int $id)
    {
        $request = new UpdateSupportTicketReadAtRequest($request->toArray(), $id);
        $response = $request->send();

        return $response->json();
    }
}
