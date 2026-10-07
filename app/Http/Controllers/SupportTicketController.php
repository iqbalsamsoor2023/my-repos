<?php

namespace App\Http\Controllers;

use App\Services\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

use function Sentry\captureException;

class SupportTicketController extends Controller
{
    protected $service;

    public function __construct(SupportTicketService $service)
    {
        $this->service = $service;
    }

    public function comment(Request $request)
    {
        try {
            $request->merge([
                'commentable_type' => 'App\Models\SupportTicket',
                'user_id' => auth()->user()->id,
            ]);

            $this->service->comment($request);
        } catch (Throwable $ex) {
            captureException($ex);

            // This endpoint is posted to by a browser form, so keep the user on the thread.
            return $this->backToThread($request)->with('supportTicketCommentError', $ex->getMessage());
        }

        return $this->backToThread($request);
    }

    protected function backToThread(Request $request): RedirectResponse
    {
        return redirect()->route('filament.admin.resources.support-tickets.comment', $request->commentable_id);
    }
}
