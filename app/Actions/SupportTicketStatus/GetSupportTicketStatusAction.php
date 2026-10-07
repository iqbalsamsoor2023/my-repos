<?php

namespace App\Actions\SupportTicketStatus;

use App\Enums\SupportTicket\SupportTicketStatusEnum;

class GetSupportTicketStatusAction
{
    public function execute()
    {
        $statuses = collect(SupportTicketStatusEnum::cases())->map(function ($status) {
            return [
                'status' => $status->label(),
            ];
        });

        return $statuses;
    }
}
