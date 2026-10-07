<?php

namespace App\Filament\Resources\SupportTickets\Widgets;

use App\Enums\SupportTicket\SupportTicketStatusEnum;
use App\Enums\User\RoleType;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketTrendChart extends ChartWidget
{
    use InteractsWithPageTable;

    public ?string $filter = 'monthly';

    protected int|string|array $columnSpan = 2;

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    private const PERIODS_COUNT = ['daily' => 30, 'weekly' => 12, 'monthly' => 12];

    /**
     * Which statuses count as completed. Add SupportTicketStatusEnum::CLOSED->value here if closed
     * tickets should be counted as completed too.
     */
    private const COMPLETED_STATUSES = [
        SupportTicketStatusEnum::COMPLETED->value,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    public function getHeading(): string
    {
        return __('support-ticket.ticket_trend');
    }

    protected function getTablePage(): string
    {
        return ListSupportTickets::class;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => __('app.daily'),
            'weekly' => __('app.weekly'),
            'monthly' => __('app.monthly'),
        ];
    }

    protected function getData(): array
    {
        $periods = $this->getPeriods();
        $rows = $this->getCountsByPeriod();

        $total = [];
        $completed = [];

        foreach ($periods as $period) {
            $total[] = (int) ($rows[$period]->total ?? 0);
            $completed[] = (int) ($rows[$period]->completed ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('support-ticket.total_tickets'),
                    'data' => $total,
                    'borderColor' => 'rgb(37, 99, 235)',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => SupportTicketStatusEnum::COMPLETED->label(),
                    'data' => $completed,
                    'borderColor' => 'rgb(21, 128, 61)',
                    'backgroundColor' => 'rgba(21, 128, 61, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $periods->map(fn (string $period): string => $this->formatPeriodLabel($period))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 8,
                        'padding' => 12,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    private function currentFilter(): string
    {
        return $this->filter ?? 'monthly';
    }

    /**
     * Every period in the range, including empty ones, so the line has no gaps.
     *
     * @return Collection<int, string>
     */
    private function getPeriods(): Collection
    {
        $count = self::PERIODS_COUNT[$this->currentFilter()] ?? 12;

        return collect(range($count - 1, 0))->map(fn (int $ago): string => match ($this->currentFilter()) {
            'daily' => now()->subDays($ago)->format('Y-m-d'),
            // ISO year and week, so these keys line up with MySQL's %x-W%v at year boundaries
            'weekly' => now()->subWeeks($ago)->format('o-\WW'),
            default => now()->subMonths($ago)->format('Y-m'),
        });
    }

    /**
     * Both series are bucketed by updated_at: support_tickets has no completed_at column, so the
     * last update is the closest thing to when a ticket reached the status it now holds.
     *
     * @return Collection<string, object>
     */
    private function getCountsByPeriod(): Collection
    {
        $placeholders = implode(',', array_fill(0, count(self::COMPLETED_STATUSES), '?'));

        $format = match ($this->currentFilter()) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-W%v', // ISO year and week, to match the keys built in getPeriods()
            default => '%Y-%m',
        };

        return $this->getPageTableQuery()
            ->reorder() // a grouped count cannot keep the table's ORDER BY under ONLY_FULL_GROUP_BY
            ->where('updated_at', '>=', $this->getRangeStart())
            ->toBase()
            // select() first to drop the table's own select list, then append the aggregates.
            ->select(DB::raw("DATE_FORMAT(updated_at, '{$format}') as period"))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ({$placeholders}) THEN 1 ELSE 0 END) as completed", self::COMPLETED_STATUSES)
            ->groupBy('period')
            ->get()
            ->keyBy('period');
    }

    private function getRangeStart(): Carbon
    {
        $count = self::PERIODS_COUNT[$this->currentFilter()] ?? 12;

        return match ($this->currentFilter()) {
            'daily' => now()->subDays($count - 1)->startOfDay(),
            'weekly' => now()->subWeeks($count - 1)->startOfWeek(),
            default => now()->subMonths($count - 1)->startOfMonth(),
        };
    }

    private function formatPeriodLabel(string $period): string
    {
        return match ($this->currentFilter()) {
            'daily' => Carbon::parse($period)->translatedFormat('j M'),
            'weekly' => __('app.week').' '.substr($period, -2),
            default => Carbon::parse($period.'-01')->translatedFormat('M Y'),
        };
    }
}
