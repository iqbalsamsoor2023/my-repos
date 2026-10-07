<?php

namespace App\Services;

use App\Actions\SupportTicket\CountUnreadSupportTicketAction;
use App\Actions\SupportTicket\CreateCommentSupportTicketAction;
use App\Actions\SupportTicket\CreateSupportTicketAction;
use App\Actions\SupportTicket\GetCommentSupportTicketAction;
use App\Actions\SupportTicket\GetSupportTicketAction;
use App\Actions\SupportTicket\GetSupportTicketByIdAction;
use App\Actions\SupportTicket\GetSupportTicketListAction;
use App\Actions\SupportTicket\UpdateSupportTicketAction;
use App\Actions\SupportTicket\UpdateSupportTicketCommentAction;
use App\Actions\SupportTicket\UpdateSupportTicketReadAtAction;
use App\Exceptions\GeneralException;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\SupportTicketCommentPosted;
use App\Notifications\SupportTicketCreated;
use App\Support\Notifications\DashboardNotification;
use Filament\Notifications\Notification as FilamentsNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SupportTicketService
{
    public function index(Request $request)
    {
        $getSupportTicketAction = new GetSupportTicketAction;
        $response = $getSupportTicketAction->execute($request);

        return $response;
    }

    public function show(int $id, Request $request)
    {
        $getSupportTicketByIdAction = new GetSupportTicketByIdAction;
        $response = $getSupportTicketByIdAction->execute($id, $request);

        return $response;
    }

    public function create(Request $request)
    {
        $supportTicketAction = new CreateSupportTicketAction;
        $supportTicket = $supportTicketAction->execute($request);

        if (Arr::has($supportTicket, 'http_code') && $supportTicket['http_code'] !== 200) {
            return response()->json($supportTicket, $supportTicket['http_code']);
        }

        $this->sendSupportTicketNotification($supportTicket);

        return $supportTicket;
    }

    public function update(Request $request, int $id)
    {
        $supportTicketAction = new UpdateSupportTicketAction;

        return $supportTicketAction->execute($request, $id);
    }

    public function read(Request $request, int $id)
    {
        $updateSupportTicketReadAtAction = new UpdateSupportTicketReadAtAction;

        return $updateSupportTicketReadAtAction->execute($request, $id);
    }

    public function comment(Request $request)
    {
        $supportTicketCommentAction = new CreateCommentSupportTicketAction;
        $supportTicketComment = $supportTicketCommentAction->execute($request);

        if (Arr::has($supportTicketComment, 'http_code') && $supportTicketComment['http_code'] !== 200) {
            $statusCode = (int) $supportTicketComment['http_code'];

            // The ERP reports failures it could not classify as http_code 0, which is not a usable response status.
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = JsonResponse::HTTP_BAD_GATEWAY;
            }

            throw new GeneralException($statusCode, $supportTicketComment['message'] ?? 'Failed to post the comment.');
        }

        $this->sendSupportTicketCommentNotification($supportTicketComment);

        return $supportTicketComment;
    }

    public function viewComment(Request $request)
    {
        $getCommentSupportTicketAction = new GetCommentSupportTicketAction;

        return $getCommentSupportTicketAction->execute($request);
    }

    public function readComment(Request $request, int $id)
    {
        $updateSupportTicketCommentAction = new UpdateSupportTicketCommentAction;

        return $updateSupportTicketCommentAction->execute($request, $id);
    }

    public function ticketList(Request $request)
    {
        $getSupportTicketListAction = new GetSupportTicketListAction($request);
        $response = $getSupportTicketListAction->execute($request);

        return $response;
    }

    protected function sendSupportTicketNotification($supportTicket)
    {
        // send notification to PM dashboard
        $user = User::findOrFail($supportTicket['data']['assigned_to']);
        $support_ticket = SupportTicket::findOrFail($supportTicket['data']['id']);

        if ($user->hasRole('Property Management') == true) {
            $user->notify(new SupportTicketCreated($support_ticket));
            $recipient = $user;

            $titleKey = 'notification.support_ticket_new_message.title';
            $params = [
                'residentName' => $support_ticket->submittedBy?->name,
                'unit' => $support_ticket->unit?->unit_number,
            ];

            DashboardNotification::make($titleKey)
                ->params($params)
                ->icon(Heroicon::OutlinedLifebuoy, 'info')
                ->viewAction(SupportTicketResource::getUrl('comment', ['supportTicketId' => $support_ticket->id]))
                ->sendToDatabase($recipient);
        } else {
            $user->notify(new SupportTicketCreated($support_ticket));
        }
    }

    protected function sendSupportTicketCommentNotification($supportTicketComment)
    {
        $supportTicketId = (int) $supportTicketComment['data']['commentable_id'];
        $supportTicket = SupportTicket::whereId($supportTicketId)->first();

        $sender = User::findOrFail($supportTicketComment['data']['user_id']);

        $receiver = null;

        if ($sender->hasRole(['Unit Owner', 'Unit Tenant'])) {
            $unit = Unit::whereId($supportTicket->unit_id)->firstOrFail();
            $receiver = User::findOrFail($unit->residence->property_management_user_id);
        } elseif ($sender->hasRole(['Property Management'])) {
            if ($sender->id == $supportTicket->submitted_by) {
                $receiver = User::findOrFail($supportTicket->assigned_to);
            } else {
                $receiver = User::findOrFail($supportTicket->submitted_by);
            }
        }

        if ($receiver) {
            $receiver->notify(new SupportTicketCommentPosted($supportTicketComment, $supportTicket, $receiver, $sender));
            $recipient = $receiver;

            if ($receiver->hasRole('Property Management')) {
                FilamentsNotification::make()
                    ->title('New message from '.$sender->name.' ('.$supportTicket->unit?->unit_number.')')
                    ->sendToDatabase($recipient);
            }
        }
    }

    public function unreadCount(Request $request, int $user_id)
    {
        $countUnreadSupportTicketAction = new CountUnreadSupportTicketAction;

        return $countUnreadSupportTicketAction->execute($user_id, $request->unit_id);
    }

    public function unreadCountByPm($user_id)
    {
        $countUnreadSupportTicketAction = new CountUnreadSupportTicketAction;

        return $countUnreadSupportTicketAction->execute($user_id, null);
    }
}
