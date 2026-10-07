<?php

namespace App\Actions\SupportTicket;

use App\Http\Integrations\MmbErp\SupportTicket\Requests\UpdateSupportTicketRequest;
use Illuminate\Http\Request;

class UpdateSupportTicketAction
{
    public function execute(Request $request, int $id)
    {
        $request = new UpdateSupportTicketRequest($this->supportTicketData($request), $id);
        $response = $request->send();

        return $response->json();
    }

    public function supportTicketData($request)
    {
        return [
            '_method' => 'PUT',
            'status' => $request->status,
        ];
    }
}
