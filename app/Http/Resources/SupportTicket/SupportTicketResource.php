<?php

namespace App\Http\Resources\SupportTicket;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use App\Models\SupportTicket;
use App\Models\SupportTicketComment;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $unitId = $this->unit ? $this->unit->id : '-';
        $userId = $this->user ? $this->user->id : '-';

        $supportTickets = SupportTicket::where('platform_identifier', 'mmb')
            ->where('unit_id', $unitId !== '-' ? $unitId : null)
            ->get();

        $read_status = false;

        if (isset($request->user_id) && $userId !== '-') {
            foreach ($supportTickets as $ticket) {
                // Case 1: Ticket itself is unread
                if (is_null($ticket->read_at) && $ticket->submitted_by == $userId && $ticket->assigned_to == $request->user_id) {
                    $read_status = true;
                    break;
                }

                // Case 2: Ticket is read but has unread comments from other users
                $hasUnreadComments = SupportTicketComment::where('commentable_id', $ticket->id)
                    ->where('commentable_type', SupportTicket::class)
                    ->where('user_id', $userId)
                    ->whereNull('read_at')
                    ->exists();

                if ($hasUnreadComments) {
                    $read_status = true;
                    break;
                }
            }
        } else {
            $read_status = $supportTickets->where('assigned_to', $userId !== '-' ? $userId : null)
                ->whereNull('read_at')
                ->isNotEmpty();
        }

        return [
            'mmb_id' => $this->mmb_id,
            'unit' => [
                'id' => $unitId,
                'unit_number' => $this->unit?->unit_number ?? '-',
            ],
            'user' => [
                'id' => $userId,
                'name' => $this->user?->name ?? '-',
                'email' => $this->user?->email ?? '-',
                'profile_image_url' => $this->user?->profile_image_url ?? '-',
            ],
            'support_ticket_indicator' => $read_status,
        ];
    }
}
