<?php

namespace App\Enums\SupportTicket;

enum SupportTicketStatusEnum: string
{
    case NEW = 'new'; // ticket has been submitted and is waiting to be reviewed
    case IN_PROGRESS = 'in progress'; // a support agent is actively working on the ticket
    case PENDING = 'pending'; // waiting input from the requester
    case COMPLETED = 'completed'; // issue has been addressed but not yet completed/resolved by the requester
    case CLOSED = 'closed'; // the ticket is officially resolbed and no further action is required
    case CANCELLED = 'cancelled'; // the ticket was withdrawn

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('status.new'),
            self::IN_PROGRESS => __('status.in_progress'),
            self::PENDING => __('status.pending'),
            self::COMPLETED => __('status.completed'),
            self::CLOSED => __('status.closed'),
            self::CANCELLED => __('status.cancelled'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'danger',
            self::IN_PROGRESS, self::PENDING => 'warning',
            self::COMPLETED, self::CLOSED => 'success',
            self::CANCELLED => 'gray',
        };
    }
}
