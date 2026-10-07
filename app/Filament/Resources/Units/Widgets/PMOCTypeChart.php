<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Enums\Residence\MoobanType;
use App\Models\Residence;
use App\Policies\UnitPolicy;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\Auth;

class PMOCTypeChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'PMOC Property Type Distribution';

    protected int|string|array $columnSpan = '3';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return UnitPolicy::isSuperAdmin(Auth::user());
    }

    protected function getData(): array
    {
        $propertyTypes = [
            1 => 'Art Gallery',
            2 => 'Community Mall',
            3 => 'Hospital',
            4 => 'Hotel',
            5 => 'Office Building',
            6 => 'Religious Organization',
            7 => 'School',
            8 => 'Shopping Mall',
            9 => 'Showroom',
            10 => 'Sport Club',
        ];

        $query = Residence::query()
            ->when($this->tableFilters['province']['value'] ?? null, function ($query, $province) {
                $query->whereHas('subdistrict.district', fn ($q) => $q->where('province_id', $province));
            })
            ->when($this->tableFilters['residence']['value'] ?? null, function ($query, $residenceId) {
                $query->where('residences.id', $residenceId);
            });

        $counts = $query->where('residences.mooban_type', MoobanType::PMOC->value)
            ->join('units', 'residences.id', '=', 'units.residence_id')
            ->selectRaw('units.property_type, COUNT(units.id) as total')
            ->groupBy('units.property_type')
            ->pluck('total', 'units.property_type')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'PMOC Property Types',
                    'data' => array_map(fn ($key) => $counts[$key] ?? 0, array_keys($propertyTypes)),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#E7E9ED', '#FF5733', '#C70039', '#900C3F',
                    ],
                ],
            ],
            'labels' => array_values($propertyTypes),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
