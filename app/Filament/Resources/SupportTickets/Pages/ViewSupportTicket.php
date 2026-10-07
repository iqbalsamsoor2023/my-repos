<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $support_ticket = SupportTicket::whereId($data['id'])->first();
        $data['residence_id'] = $support_ticket?->unit?->residence_id;
        $data['chat_category_id'] = $support_ticket?->chatCategoryItem?->chatCategory?->id ?? 5;

        return $data;
    }
}
