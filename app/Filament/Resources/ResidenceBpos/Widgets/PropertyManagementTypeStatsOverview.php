<?php

namespace App\Filament\Resources\ResidenceBpos\Widgets;

use App\Enums\Residence\MoobanType;
use App\Support\WidgetColorPalette;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PropertyManagementTypeStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getCards(): array
    {
        $filters = $this->tableFilters['province_filters'] ?? [];

        $propertyManagementTypes = [
            1 => __('residence.personal_property_management'),
            2 => __('residence.company_property_management'),
            3 => __('residence.developer_property_management'),
            4 => __('residence.abandoned'),
            5 => __('residence.no_info'),
        ];

        $query = DB::table('residence_stats_view')
            ->where('mooban_type', MoobanType::PUBLIC->value)
            ->when(! empty($filters['province']), fn ($q) => $q->whereIn('province_id', (array) $filters['province']))
            ->when(! empty($filters['district']), fn ($q) => $q->whereIn('district_id', (array) $filters['district']))
            ->when(! empty($filters['subdistrict']), fn ($q) => $q->whereIn('subdistrict_id', (array) $filters['subdistrict']));

        $counts = $query
            ->selectRaw('property_management_type, COUNT(*) as total')
            ->groupBy('property_management_type')
            ->pluck('total', 'property_management_type');
        $grandTotal = (int) $counts->sum();

        // Build cards (always show all 3 types) with colored label and value
        $palette = WidgetColorPalette::propertyManagementTypes();

        return collect($propertyManagementTypes)->map(
            function ($label, $key) use ($counts, $palette, $grandTotal) {
                $count = $counts[$key] ?? 0;
                $color = $palette[$key]['color'] ?? '#64748B';
                $percentage = $grandTotal > 0 ? round(((int) $count / $grandTotal) * 100, 2) : 0;

                return Stat::make(
                    new HtmlString("<span class=\"font-semibold\" style=\"color:{$color};\">{$label}</span>"),
                    number_format((int) $count)
                )
                    ->value(new HtmlString("\n                    <div style=\"display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;\">\n                        <span style=\"color:{$color}; font-weight:bold; font-size:1.5rem;\">".number_format((int) $count)."</span>\n                        <span style=\"font-size:1rem;\">({$percentage}%)</span>\n                    </div>\n                "))
                    ->description(__('residence.from_total_residences', ['total' => number_format($grandTotal)]))
                    ->color(WidgetColorPalette::statColorFromHex($color));
            }
        )->values()->toArray();
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
