<?php

namespace App\Filament\Resources\ResidenceBpos\Widgets;

use App\Enums\Residence\MoobanType;
use App\Support\WidgetColorPalette;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class PropertyManagementTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.property_management_type_distribution');
    }

    protected function getData(): array
    {
        $filters = $this->tableFilters['province_filters'] ?? [];

        $types = WidgetColorPalette::propertyManagementTypes();

        $query = DB::table('residence_stats_view')
            ->where('mooban_type', MoobanType::PUBLIC->value)
            ->when(! empty($filters['province']), fn ($q) => $q->whereIn('province_id', (array) $filters['province']))
            ->when(! empty($filters['district']), fn ($q) => $q->whereIn('district_id', (array) $filters['district']))
            ->when(! empty($filters['subdistrict']), fn ($q) => $q->whereIn('subdistrict_id', (array) $filters['subdistrict']));

        $counts = $query
            ->selectRaw('property_management_type, COUNT(*) as total')
            ->groupBy('property_management_type')
            ->pluck('total', 'property_management_type')
            ->toArray();

        // Build chart data (always show all types)
        return [
            'labels' => collect($types)->pluck('label')->toArray(),
            'datasets' => [
                [
                    'label' => __('residence.management_types'),
                    'data' => collect($types)->keys()->map(
                        fn ($key) => $counts[$key] ?? 0
                    )->toArray(),
                    'backgroundColor' => collect($types)->pluck('color')->toArray(),
                    'hoverOffset' => 6,
                    'borderWidth' => 1,
                    'borderColor' => 'white',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
