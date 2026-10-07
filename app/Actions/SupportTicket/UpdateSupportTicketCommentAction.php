<?php

namespace App\Actions\SupportTicket;

use App\Http\Integrations\MmbErp\SupportTicket\Requests\UpdateSupportTicketCommentReadRequest;
use Illuminate\Http\Request;

class UpdateSupportTicketCommentAction
{
    public function execute(Request $request, int $id)
    {
        $request = new UpdateSupportTicketCommentReadRequest($this->supportTicketData($request), $id);
        $response = $request->send();

        return $response->json();
    }

    public function supportTicketData($request)
    {
        $data = [
            '_method' => 'PUT',
        ];

        if (isset($request->user_id)) {
            $data = array_merge($data, [
                'user_id' => (int) $request->user_id,
            ]);
        }

        return $data;
    }
}
