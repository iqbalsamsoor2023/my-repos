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

class SupportTicketCategoryItemChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Topics are ranked busiest first and anything past this is rolled into a single tail bar, so
     * no tickets are dropped. Around thirty topics are in use, so the tail rarely appears.
     */
    private const VISIBLE_TOPICS = 40;

    private const ROW_HEIGHT = 26;

    private const BAR_COLOUR = 'rgb(37, 99, 235)';

    private const TAIL_COLOUR = 'rgb(148, 163, 184)';

    /**
     * @var Collection<string, int>|null
     */
    private ?Collection $topicCounts = null;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
        ]) ?? false;
    }

    public function getHeading(): string
    {
        return __('support-ticket.tickets_by_topic');
    }

    protected function getTablePage(): string
    {
        return ListSupportTickets::class;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Grows with the number of bars: at forty topics a fixed height would squeeze each bar into a
     * few pixels and the topic names would be unreadable.
     */
    protected function getMaxHeight(): ?string
    {
        $topics = $this->getTopicCounts()->count();

        $bars = min($topics, self::VISIBLE_TOPICS) + ($topics > self::VISIBLE_TOPICS ? 1 : 0);

        return max(320, ($bars * self::ROW_HEIGHT) + 72).'px';
    }

    protected function getData(): array
    {
        $ranked = $this->getTopicCounts()->sortDesc();

        $visible = $ranked->take(self::VISIBLE_TOPICS);
        $tail = $ranked->slice(self::VISIBLE_TOPICS);

        $labels = $visible->keys()->all();
        $data = $visible->values()->all();
        $colours = array_fill(0, $visible->count(), self::BAR_COLOUR);

        if ($tail->isNotEmpty()) {
            $labels[] = __('support-ticket.other_topics', ['count' => $tail->count()]);
            $data[] = (int) $tail->sum();
            $colours[] = self::TAIL_COLOUR;
        }

        return [
            'datasets' => [[
                'label' => __('support-ticket.total_tickets'),
                'data' => $data,
                'backgroundColor' => $colours,
                'borderColor' => $colours,
                'borderRadius' => 4,
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
            'indexAxis' => 'y', // horizontal bars, so the topic names stay readable
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    /**
     * Ticket counts keyed by topic label.
     *
     * Tickets with no category item are left out: they have no topic to attribute, and the category
     * doughnut already reports them under Others.
     *
     * Memoised because getMaxHeight() and getData() both need it within the same request.
     *
     * @return Collection<string, int>
     */
    private function getTopicCounts(): Collection
    {
        return $this->topicCounts ??= $this->fetchTopicCounts();
    }

    /**
     * @return Collection<string, int>
     */
    private function fetchTopicCounts(): Collection
    {
        $countsByItem = $this->getPageTableQuery()
            ->reorder() // a grouped count cannot keep the table's ORDER BY under ONLY_FULL_GROUP_BY
            ->whereNotNull('chat_category_item_id')
            ->toBase()
            // select() rather than selectRaw(): the table query already selects support_tickets.*
            // plus the comments_count subquery, and those cannot survive the GROUP BY.
            ->select('chat_category_item_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('chat_category_item_id')
            ->pluck('aggregate', 'chat_category_item_id');

        $labels = $this->getTopicLabels($countsByItem->keys());

        $counts = collect();

        foreach ($countsByItem as $itemId => $aggregate) {
            $label = $labels[$itemId] ?? __('support-ticket.unknown_topic');

            $counts[$label] = ($counts[$label] ?? 0) + (int) $aggregate;
        }

        return $counts;
    }

    /**
     * Several categories have a topic called Others, so a title on its own is not always enough to
     * tell two bars apart. Any title used more than once is qualified with its parent category.
     *
     * @param  Collection<int, mixed>  $itemIds
     * @return Collection<int, string>
     */
    private function getTopicLabels(Collection $itemIds): Collection
    {
        $isThai = app()->getLocale() === 'th';

        $items = ChatCategoryItem::whereIn('id', $itemIds->all())->get();
        $categories = ChatCategory::whereIn('id', $items->pluck('chat_category_id')->unique()->all())->get();

        $titleOf = fn (ChatCategoryItem $item): string => $isThai && filled($item->title_in_thai)
            ? $item->title_in_thai
            : (string) $item->title;

        $duplicated = $items->groupBy($titleOf)->filter(fn (Collection $group): bool => $group->count() > 1)->keys();

        return $items->mapWithKeys(function (ChatCategoryItem $item) use ($titleOf, $categories, $duplicated, $isThai): array {
            $title = $titleOf($item);

            if ($duplicated->contains($title)) {
                $category = $categories->firstWhere('id', $item->chat_category_id);
                $categoryName = $isThai && filled($category?->name_in_thai) ? $category->name_in_thai : $category?->name;

                if (filled($categoryName)) {
                    $title .= ' ('.$categoryName.')';
                }
            }

            return [$item->id => $title];
        });
    }
}
