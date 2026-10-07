<?php

namespace App\Actions\SupportTicket;

use App\Http\Resources\SupportTicket\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\Unit;
use App\Models\UnitUser;
use Illuminate\Support\Facades\DB;

class GetSupportTicketListAction
{
    public function execute($request)
    {
        $units = Unit::where('residence_id', $request['residence_id'])->pluck('id');
        $support_tickets = SupportTicket::where('platform_identifier', 'mmb')->whereIn('unit_id', $units)->latest()->get();

        if (empty($support_tickets) == false) {
            $assigned_to = $support_tickets->pluck('assigned_to')->toArray();
            $submitted_by = $support_tickets->pluck('submitted_by')->toArray();
            $unit_ids = $support_tickets->pluck('unit_id')->unique()->toArray();

            $user_ids = array_unique(array_merge($assigned_to, $submitted_by));
        }

        $unit_users = UnitUser::whereRelation('unit', 'residence_id', $request['residence_id'])
            ->where(function ($query) use ($request) {
                $query->orWhereHas('unit', function ($query) use ($request) {
                    $query->where('unit_number', 'LIKE', '%'.$request['unit_number'].'%');
                })
                    ->orWhereHas('user', function ($query) use ($request) {
                        $query->where('name', 'LIKE', '%'.$request['name'].'%')
                            ->orWhere('email', 'LIKE', '%'.$request['email'].'%');
                    });
            })
            ->whereIn('unit_id', $unit_ids)
            ->whereIn('user_id', $user_ids)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereHas('unit.supportTickets')
                    ->orWhereHas('unit.supportTickets.comments');
            })
            ->with(['unit.supportTickets', 'unit.supportTickets.comments']) // eager load relations for ordering
            ->orderByDesc(DB::raw('GREATEST(
                (SELECT MAX(support_tickets.created_at) FROM mmbcnerm.support_tickets WHERE support_tickets.unit_id = unit_user.unit_id),
                (SELECT MAX(comments.created_at) FROM mmbcnerm.comments WHERE comments.commentable_id IN (SELECT id FROM mmbcnerm.support_tickets WHERE support_tickets.unit_id = unit_user.unit_id))
            )'))
            ->paginate(10);

        return SupportTicketResource::collection($unit_users);
    }
}
