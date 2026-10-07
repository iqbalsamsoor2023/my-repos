<?php

namespace App\Actions\SupportTicket;

use App\Models\ErpComment;
use App\Models\SupportTicket;

class CountUnreadSupportTicketAction
{
    public function execute(int $user_id, ?int $unit_id)
    {
        if (isset($unit_id)) {
            $supportTicket = SupportTicket::where('assigned_to', $user_id)
                ->where('unit_id', $unit_id)
                ->whereNull('deleted_at');

            $data['count_support_ticket'] = $this->totalUnreadSupportTicket($supportTicket, $unit_id, $user_id);
        } else {
            // For PM: get all tickets assigned to user
            $supportTicket = SupportTicket::where('assigned_to', $user_id)
                ->whereNull('deleted_at');

            $data['support_ticket_pm'] = $this->totalUnreadSupportTicketPm($supportTicket, $user_id);
        }

        return $data;
    }

    private function totalUnreadSupportTicket($supportTicket, $unit_id, $user_id): int
    {
        $supportTicketCount = $supportTicket->whereNull('read_at')->count();

        $commentCount = ErpComment::select('comments.*')
            ->leftJoin('support_tickets', 'support_tickets.id', '=', 'comments.commentable_id')
            ->where('support_tickets.unit_id', '=', $unit_id)
            ->where('comments.user_id', '!=', $user_id)
            ->whereNull('support_tickets.deleted_at')
            ->whereNull('support_tickets.read_at')
            ->whereNull('comments.read_at')
            ->whereNull('comments.deleted_at')
            ->count();

        return $supportTicketCount + $commentCount;
    }

    private function totalUnreadSupportTicketPm($supportTicket, $user_id): int
    {
        // Clone to preserve original query
        $ticketQuery = clone $supportTicket;

        $ticketIds = $ticketQuery->pluck('id');

        // Count unread tickets
        $unreadTicketCount = $supportTicket->whereNull('read_at')->count();

        // Count unread comments on those tickets from other users
        $unreadCommentCount = ErpComment::whereIn('commentable_id', $ticketIds)
            ->where('commentable_type', SupportTicket::class)
            ->where('user_id', '!=', $user_id)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->count();

        return $unreadTicketCount + $unreadCommentCount;
    }
}
