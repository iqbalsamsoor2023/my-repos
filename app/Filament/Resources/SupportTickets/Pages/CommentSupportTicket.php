<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Actions\SupportTicket\GetCommentSupportTicketAction;
use App\Enums\SupportTicket\SupportTicketStatusEnum;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\Audit;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Request;
use Livewire\Attributes\Computed;
use Throwable;

class CommentSupportTicket extends Page
{
    protected static string $resource = SupportTicketResource::class;

    protected string $view = 'filament.resources.support-ticket-resource.pages.comment-support-ticket';

    public $supportTicketId;

    public array $comments = [];

    public int $commentPage = 1;

    public bool $hasOlderComments = false;

    public bool $failedToLoad = false;

    public function mount($supportTicketId): void
    {
        $this->supportTicketId = $supportTicketId;

        $this->loadComments();
    }

    /**
     * Prepend the previous page of the thread, keeping the oldest first order.
     */
    public function loadOlderComments(): void
    {
        if (! $this->hasOlderComments) {
            return;
        }

        [$comments, $hasOlderComments] = $this->fetchComments($this->commentPage + 1);

        if ($comments === []) {
            $this->hasOlderComments = false;

            return;
        }

        $this->commentPage++;
        $this->hasOlderComments = $hasOlderComments;
        $this->comments = array_merge($comments, $this->comments);
    }

    public function loadComments(): void
    {
        $this->commentPage = 1;

        [$this->comments, $this->hasOlderComments] = $this->fetchComments($this->commentPage);
    }

    /**
     * The ticket this thread belongs to, derived from the id rather than stored.
     *
     * #[Computed] is load bearing here, do not swap it for a property:
     * - mount() only runs on the first request, so a property assigned there would be null
     *   on every later Livewire update (changing status, loading older comments).
     * - A public property would serialise the model into the snapshot and send it to the
     *   browser and back on every request. This model appends attachment_url, so each of
     *   those round trips costs an extra media query and a COS lookup.
     * - It memoises per request, so getTitle(), the view and updateStatus() share one query
     *   instead of three. updateStatus() calls unset() on it to force a re-read after saving.
     */
    #[Computed]
    public function supportTicket(): ?SupportTicket
    {
        return SupportTicket::with('unit')->find($this->supportTicketId);
    }

    public function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label(__('support-ticket.change_status'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->size(Size::Small)
            ->outlined()
            ->modalHeading(__('support-ticket.change_status'))
            ->modalSubmitActionLabel(__('support-ticket.change_status'))
            ->modalWidth(Width::Medium)
            ->fillForm(fn (): array => ['status' => $this->supportTicket?->status])
            ->schema([
                Select::make('status')
                    ->label(__('app.status'))
                    ->options(SupportTicketStatusEnum::options())
                    ->native(false)
                    ->required(),
            ])
            ->action(fn (array $data) => $this->updateStatus($data['status']));
    }

    public function getTitle(): string
    {
        $caseGeneratedNo = $this->supportTicket?->case_generated_no;

        return $caseGeneratedNo
            ? __('support-ticket.support_ticket_comments').' · '.$caseGeneratedNo
            : __('support-ticket.support_ticket_comments');
    }

    public function getHeading(): string
    {
        return __('support-ticket.support_ticket_comments');
    }

    /**
     * Mirrors how EditSupportTicket persists a change, so the audit trail stays consistent.
     */
    protected function updateStatus(string $status): void
    {
        $supportTicket = $this->supportTicket;

        if (! $supportTicket) {
            return;
        }

        $original = $supportTicket->getOriginal();
        $supportTicket->status = $status;
        $changes = $supportTicket->getDirty();

        if ($changes === []) {
            return;
        }

        $supportTicket->save();

        Audit::create([
            'user_type' => get_class(auth()->user()),
            'user_id' => auth()->id(),
            'event' => 'updated',
            'auditable_type' => get_class($supportTicket),
            'auditable_id' => $supportTicket->id,
            'old_values' => array_intersect_key($original, $changes),
            'new_values' => $changes,
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Clear the computed cache so the re-render reads the saved row back rather than the
        // instance mutated above. Only safe to drop if supportTicket() stops being #[Computed].
        unset($this->supportTicket);

        Notification::make()
            ->success()
            ->title(__('support-ticket.status_updated'))
            ->body(SupportTicketStatusEnum::tryFrom($status)?->label())
            ->send();
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: bool}
     */
    protected function fetchComments(int $page): array
    {
        $getCommentSupportTicketAction = new GetCommentSupportTicketAction;

        try {
            $response = $getCommentSupportTicketAction->execute(new Request([
                'commentable_id' => $this->supportTicketId,
                'page' => $page,
            ]));
        } catch (Throwable $exception) {
            report($exception);

            $this->failedToLoad = true;

            return [[], false];
        }

        $this->failedToLoad = false;

        // The API returns the newest comment first, the chat reads the other way around.
        return [
            array_reverse($response['data'] ?? []),
            (bool) ($response['paginatorInfo']['hasMorePages'] ?? false),
        ];
    }
}
