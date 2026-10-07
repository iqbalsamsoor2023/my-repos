<?php

namespace App\Filament\Resources\ResidenceAmenitySummaries\Widgets;

use App\Enums\FacilityAndAmenity\FacilityAmenityTypeEnum;
use App\Enums\Residence\MoobanType;
use App\Enums\User\RoleType;
use App\Models\Residence;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class AmenityStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->hasRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
        ]) ?? false;
    }

    protected function getStats(): array
    {
        $filters = $this->tableFilters['province_filters'] ?? [];
        $residenceFilters = $this->tableFilters['mooban'] ?? [];

        // Step 1: Base residence query
        $residenceQuery = Residence::query()
            ->whereNull('deleted_at')
            ->where('mooban_type', MoobanType::PUBLIC->value);

        foreach (['name', 'mooban_type', 'sub_type'] as $filter) {
            if (!empty($residenceFilters[$filter])) {
                $residenceQuery->whereIn(
                    $filter === 'name' ? 'id' : $filter,
                    (array) $residenceFilters[$filter]
                );
            }
        }

        // Apply province/district/subdistrict filters
        $residenceQuery
            ->when(!empty($filters['province']), fn($q) => $q->whereHas('subdistrict.district.province', fn($q2) => $q2->whereIn('id', (array) $filters['province'])))
            ->when(!empty($filters['district']), fn($q) => $q->whereHas('subdistrict.district', fn($q2) => $q2->whereIn('id', (array) $filters['district'])))
            ->when(!empty($filters['subdistrict']), fn($q) => $q->whereHas('subdistrict', fn($q2) => $q2->whereIn('id', (array) $filters['subdistrict'])));

        $totalResidences = $residenceQuery->count();

        // Count top 15 amenities
        $amenityData = DB::table('facilities_and_amenities as f')
            ->leftJoin('residence_amenity as ra', function ($join) use ($residenceQuery) {
                $join->on('f.id', '=', 'ra.facility_and_amenity_id')
                     ->whereNull('ra.deleted_at')
                     ->whereIn('ra.residence_id', $residenceQuery->select('id'));
            })
            ->where('f.type', FacilityAmenityTypeEnum::AMENITY->value)
            ->whereNull('f.deleted_at')
            ->select('f.name', DB::raw('COUNT(DISTINCT ra.residence_id) as residence_count'))
            ->groupBy('f.id', 'f.name')
            ->orderByDesc('residence_count')
            ->limit(15)
            ->get();

        // Count residences with no amenities assigned
        $nullCount = DB::table('residences as r')
            ->whereIn('r.id', $residenceQuery->select('id'))
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('residence_amenity as ra')
                    ->whereColumn('ra.residence_id', 'r.id')
                    ->whereNull('ra.deleted_at');
            })
            ->count();

        // Build stats
        $colorPalette = [
            '#4E79A7', // Indigo Blue
            '#F28E2B', // Amber Orange
            '#E15759', // Coral Red
            '#76B7B2', // Sea Green
            '#59A14F', // Leaf Green
            '#EDC949', // Mustard Yellow
            '#AF7AA1', // Lavender Purple
            '#FF9DA7', // Pink Rose
            '#9C755F', // Earth Brown
            '#BAB0AC', // Ash Gray
            '#8CD17D', // Mint Green
            '#B6992D', // Golden Olive
            '#D37295', // Dusty Pink
            '#FABFD2', // Cotton Candy
            '#79706E', // Stone Gray
        ];

        $stats = [];

        foreach ($amenityData as $index => $row) {
            $name = $row->name ?? __('user.not_yet_set');
            $count = $row->residence_count ?? 0;
            $percentage = $totalResidences > 0 ? round(($count / $totalResidences) * 100, 2) : 0;
            $color = $colorPalette[$index] ?? '#6B7280';

            $stats[] = Stat::make(new HtmlString("<span style='color:{$color};font-weight:bold;'>{$name}</span>"), $count)
                ->value(new HtmlString("<div style='display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;'>
                    <span style='color:{$color}; font-weight:bold; font-size:1.75rem;'>{$count}</span>
                </div>"))
                ->description(new HtmlString("<span><strong class=\"text-black dark:text-white\">{$percentage}%</strong> of {$totalResidences}</span>"));
        }

        if ($nullCount > 0) {
            $percentage = $totalResidences > 0 ? round(($nullCount / $totalResidences) * 100, 2) : 0;
            $stats[] = Stat::make(new HtmlString("<span style='color:#D3D3D3;font-weight:bold;'>". __('user.not_yet_set') ."</span>"), $nullCount)
                ->value(new HtmlString("<div style='display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;'>
                    <span style='color:#D3D3D3; font-weight:bold; font-size:1.75rem;'>{$nullCount}</span>
                </div>"))
                ->description(new HtmlString("<span><strong class=\"text-black dark:text-white\">{$percentage}%</strong> residences without any amenity</span>"));
        }

        return $stats;
    }

    public function updatedTableFilters(): void
    {
        $this->emitSelf('refresh');
    }
}
