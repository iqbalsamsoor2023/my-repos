<?php

namespace App\Filament\Resources\ResidenceBpos\Widgets;

use App\Enums\Residence\MoobanType;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class ResidenceDeveloperChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('residence.top_30_developers');
    }

    protected function getData(): array
    {
        $filters = $this->tableFilters['province_filters'] ?? [];

        // Use denormalized read model (no JOINs needed)
        $query = DB::table('residence_stats_view')
            ->where('mooban_type', MoobanType::PUBLIC->value)
            ->whereNotNull('developer_name')
            ->when(! empty($filters['province']), fn ($q) => $q->whereIn('province_id', (array) $filters['province']))
            ->when(! empty($filters['district']), fn ($q) => $q->whereIn('district_id', (array) $filters['district']))
            ->when(! empty($filters['subdistrict']), fn ($q) => $q->whereIn('subdistrict_id', (array) $filters['subdistrict']));

        $topDevelopers = $query
            ->select('developer_name as developer', DB::raw('COUNT(*) as total'))
            ->groupBy('developer_id', 'developer_name')
            ->orderByDesc('total')
            ->limit(30)
            ->pluck('total', 'developer')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('residence.number_of_residences_by_developers'),
                    'data' => array_values($topDevelopers),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgba(59, 130, 246, 0.5)',
                    'fill' => true,
                ],
            ],
            'labels' => array_keys($topDevelopers),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
