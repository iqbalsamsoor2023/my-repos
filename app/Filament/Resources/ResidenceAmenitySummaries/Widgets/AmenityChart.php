<?php

namespace App\Filament\Resources\ResidenceAmenitySummaries\Widgets;

use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Models\Residence;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class AmenityChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Amenity Chart';

    protected ?string $maxHeight = '400px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        // Step 1: Base residence query for filtering
        $residenceQuery = Residence::query()->whereNull('deleted_at');

        $provinceFilters = $this->tableFilters['province_filters'] ?? [];
        $residenceFilters = $this->tableFilters['mooban'] ?? [];

        // Apply residence filters
        foreach (['name', 'mooban_type', 'sub_type'] as $filter) {
            if (!empty($residenceFilters[$filter])) {
                $residenceQuery->whereIn($filter === 'name' ? 'id' : $filter, (array) $residenceFilters[$filter]);
            }
        }

        // Apply province/district/subdistrict filters
        $residenceQuery
            ->when(!empty($provinceFilters['province']), fn($q) => $q->whereHas('subdistrict.district.province', fn($q2) => $q2->whereIn('id', (array) $provinceFilters['province'])))
            ->when(!empty($provinceFilters['district']), fn($q) => $q->whereHas('subdistrict.district', fn($q2) => $q2->whereIn('id', (array) $provinceFilters['district'])))
            ->when(!empty($provinceFilters['subdistrict']), fn($q) => $q->whereHas('subdistrict', fn($q2) => $q2->whereIn('id', (array) $provinceFilters['subdistrict'])));

        // Join amenities directly and count usage
        $data = DB::table('facilities_and_amenities as f')
            ->leftJoin('residence_amenity as ra', function ($join) use ($residenceQuery) {
                $join->on('f.id', '=', 'ra.facility_and_amenity_id')
                     ->where('ra.is_active', true)
                     ->whereNull('ra.deleted_at');

                // Filter by residences in one subquery
                $join->whereIn('ra.residence_id', $residenceQuery->select('id'));
            })
            ->where('f.type', FacilityAmenityTypeEnum::AMENITY->value)
            ->where('f.is_active', true)
            ->select('f.name', DB::raw('COUNT(ra.id) as amenity_count'))
            ->groupBy('f.id', 'f.name')
            ->orderBy('f.name')
            ->get();

        // Chart
        return [
            'datasets' => [
                [
                    'label' => 'Amenity Count',
                    'data' => $data->pluck('amenity_count')->toArray(),
                    'backgroundColor' => $this->generateColors($data->count()),
                    'borderColor' => $this->generateColors($data->count()),
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function generateColors(int $count): array
    {
        $baseColors = [
            '#4E79A7','#F28E2B','#E15759','#76B7B2','#59A14F','#EDC949',
            '#AF7AA1','#FF9DA7','#9C755F','#BAB0AC','#8CD17D','#B6992D',
            '#D37295','#FABFD2','#79706E','#D4A6C8','#A6D854','#FFB347',
            '#C2B280','#D3D3D3',
        ];

        return array_slice(array_merge($baseColors, $baseColors), 0, $count);
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
