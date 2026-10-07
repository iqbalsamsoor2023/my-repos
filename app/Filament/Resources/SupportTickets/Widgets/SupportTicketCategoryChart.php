<?php

namespace App\Filament\Resources\SupportTickets\Widgets;

use App\Enums\User\RoleType;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Models\Erp\ChatCategory;
use App\Models\Erp\ChatCategoryItem;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketCategoryChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    /**
     * Tickets with no category item are shown as Others, which is this row in chat_categories.
     */
    private const OTHERS_CATEGORY_ID = 5;

    private const COLOURS = [
        1 => 'rgb(37, 99, 235)',   // General Questions
        2 => 'rgb(180, 83, 9)',    // Report Issues
        3 => 'rgb(109, 40, 217)',  // Need Help?
        4 => 'rgb(21, 128, 61)',   // Suggestions & Feedback
        5 => 'rgb(107, 114, 128)', // Others
    ];

    private const FALLBACK_COLOUR = 'rgb(148, 163, 184)';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    public function getHeading(): string
    {
        return __('support-ticket.tickets_by_category');
    }

    protected function getTablePage(): string
    {
        return ListSupportTickets::class;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = $this->getCategoryCounts();
        $isThai = app()->getLocale() === 'th';

        $labels = [];
        $data = [];
        $colours = [];

        foreach (ChatCategory::orderBy('id')->get() as $category) {
            $total = (int) ($counts[$category->id] ?? 0);

            // An empty slice is invisible on the ring but still takes a legend entry.
            if ($total === 0) {
                continue;
            }

            $labels[] = $isThai && filled($category->name_in_thai) ? $category->name_in_thai : $category->name;
            $data[] = $total;
            $colours[] = self::COLOURS[$category->id] ?? self::FALLBACK_COLOUR;
        }

        return [
            'datasets' => [[
                'label' => __('support-ticket.tickets_by_category'),
                'data' => $data,
                'backgroundColor' => $colours,
                'borderColor' => $colours,
            ]],
            'labels' => $labels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'cutout' => '60%',
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
        ];
    }

    /**
     * Counted from the page's table query so the chart always matches the rows below.
     *
     * The counts are grouped by category item and folded up to the parent category in PHP rather
     * than joined: the resource's base query filters on an unqualified platform_identifier, and
     * chat_category_items carries that column too, so joining makes the where clause ambiguous.
     *
     * @return Collection<int, int>
     */
    private function getCategoryCounts(): Collection
    {
        $countsByItem = $this->getPageTableQuery()
            ->reorder() // a grouped count cannot keep the table's ORDER BY under ONLY_FULL_GROUP_BY
            ->toBase()
            // select() rather than selectRaw(): the table query already selects support_tickets.*
            // plus the comments_count subquery, and those cannot survive the GROUP BY.
            ->select('chat_category_item_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('chat_category_item_id')
            ->pluck('aggregate', 'chat_category_item_id');

        $categoryIdByItem = ChatCategoryItem::pluck('chat_category_id', 'id');

        $counts = collect();

        foreach ($countsByItem as $itemId => $aggregate) {
            $categoryId = blank($itemId)
                ? self::OTHERS_CATEGORY_ID
                : (int) ($categoryIdByItem[$itemId] ?? self::OTHERS_CATEGORY_ID);

            $counts[$categoryId] = ($counts[$categoryId] ?? 0) + (int) $aggregate;
        }

        return $counts;
    }
}
