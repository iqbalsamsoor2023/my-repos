<?php

namespace App\Filament\Resources\Pets\Widgets;

use App\Policies\PetPolicy;
use App\Services\PetWidgetDataService;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PetsTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        return PetPolicy::hasDashboardAccess(Auth::user());
    }

    protected function getStats(): array
    {
        $data = PetWidgetDataService::getScopedDataForUser(Auth::user(), $this->tableFilters ?? []);
        $total = (int) ($data['total_pets'] ?? 0);
        $counts = $data['type_counts'] ?? [];
        $colorMap = WidgetColorPalette::petTypeColors();

        $entries = [
            ['key' => 'dog', 'label' => __('pet.dog')],
            ['key' => 'cat', 'label' => __('pet.cat')],
            ['key' => 'not_set', 'label' => __('pet.not_yet_set')],
        ];

        $stats = [];

        foreach ($entries as $entry) {
            $count = (int) ($counts[$entry['key']] ?? 0);
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $color = $colorMap[$entry['key']];

            $stats[] = Stat::make(
                new HtmlString("<span style=\"color:{$color};font-weight:bold;\">{$entry['label']}</span>"),
                $count
            )->value(new HtmlString("
                <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">
                    <span style=\"color:{$color}; font-weight:bold; font-size:1.75rem;\">{$count}</span>
                    <span style=\"font-size:1.15rem; color:#fff; margin-left:0.5rem;\">({$percentage}%)</span>
                </div>
            "))->description(__('pet.widgets.from_total_pets', ['total' => $total]));
        }

        return $stats;
    }
}
