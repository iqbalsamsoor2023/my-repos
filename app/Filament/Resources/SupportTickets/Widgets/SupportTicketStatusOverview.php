<?php

namespace App\Filament\Resources\SupportTickets\Widgets;

use App\Enums\SupportTicket\SupportTicketStatusEnum;
use App\Enums\User\RoleType;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketStatusOverview extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.widgets.support-ticket.status-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Colours follow the overview design rather than the badge colours used in the table,
     * where new is red to flag it as unattended.
     */
    private const PALETTE = [
        'total' => ['color' => '#1d4ed8', 'colorDark' => '#60a5fa', 'tint' => 'rgba(29, 78, 216, 0.1)'],
        SupportTicketStatusEnum::NEW->value => ['color' => '#2563eb', 'colorDark' => '#60a5fa', 'tint' => 'rgba(37, 99, 235, 0.1)'],
        SupportTicketStatusEnum::IN_PROGRESS->value => ['color' => '#b45309', 'colorDark' => '#fbbf24', 'tint' => 'rgba(180, 83, 9, 0.12)'],
        SupportTicketStatusEnum::PENDING->value => ['color' => '#6d28d9', 'colorDark' => '#a78bfa', 'tint' => 'rgba(109, 40, 217, 0.1)'],
        SupportTicketStatusEnum::COMPLETED->value => ['color' => '#15803d', 'colorDark' => '#4ade80', 'tint' => 'rgba(21, 128, 61, 0.12)'],
        SupportTicketStatusEnum::CLOSED->value => ['color' => '#374151', 'colorDark' => '#9ca3af', 'tint' => 'rgba(55, 65, 81, 0.12)'],
        SupportTicketStatusEnum::CANCELLED->value => ['color' => '#b91c1c', 'colorDark' => '#f87171', 'tint' => 'rgba(185, 28, 28, 0.1)'],
    ];

    private const ICONS = [
        'total' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        SupportTicketStatusEnum::NEW->value => 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
        SupportTicketStatusEnum::IN_PROGRESS->value => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
        SupportTicketStatusEnum::PENDING->value => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
        SupportTicketStatusEnum::COMPLETED->value => 'M4.5 12.75l6 6 9-13.5',
        SupportTicketStatusEnum::CLOSED->value => 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        SupportTicketStatusEnum::CANCELLED->value => 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    protected function getTablePage(): string
    {
        return ListSupportTickets::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $counts = $this->getStatusCounts();
        $total = (int) $counts->sum();

        $cards = [[
            'key' => 'total',
            'label' => __('support-ticket.total_tickets'),
            'value' => $total,
            'percentage' => $total > 0 ? 100 : 0,
            'percentageLabel' => ($total > 0 ? 100 : 0).'%',
            ...self::PALETTE['total'],
            'icon' => self::ICONS['total'],
        ]];

        foreach (SupportTicketStatusEnum::cases() as $case) {
            $value = (int) ($counts[$case->value] ?? 0);
            $percentage = $total > 0 ? (int) round($value / $total * 100) : 0;

            $cards[] = [
                'key' => $case->value,
                'label' => $case->label(),
                'value' => $value,
                'percentage' => $percentage,
                // A handful out of thousands still rounds to 0%, which reads as "none".
                'percentageLabel' => ($value > 0 && $percentage === 0) ? '<1%' : $percentage.'%',
                ...self::PALETTE[$case->value],
                'icon' => self::ICONS[$case->value],
            ];
        }

        return ['cards' => $cards];
    }

    /**
     * Counted from the page's own table query, so the numbers always match the rows below,
     * including the role scoping in the resource and whatever filters are applied.
     */
    private function getStatusCounts(): Collection
    {
        return $this->getPageTableQuery()
            ->reorder() // a grouped count cannot keep the table's ORDER BY under ONLY_FULL_GROUP_BY
            ->toBase()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');
    }
}
