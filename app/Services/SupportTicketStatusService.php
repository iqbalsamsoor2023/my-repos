<?php

namespace App\Services;

use App\Actions\SupportTicketStatus\GetSupportTicketStatusAction;

class SupportTicketStatusService
{
    public function index()
    {
        $getSupportTicketStatusAction = new GetSupportTicketStatusAction;
        $response = $getSupportTicketStatusAction->execute();

        return $response;
    }
}
