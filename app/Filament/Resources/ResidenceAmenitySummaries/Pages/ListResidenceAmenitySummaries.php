<?php

namespace App\Filament\Resources\ResidenceAmenitySummaries\Pages;

use App\Filament\Resources\ResidenceAmenitySummaries\Widgets\AmenityChart;
use App\Filament\Resources\ResidenceAmenitySummaries\Widgets\FacilityChart;
use App\Filament\Resources\ResidenceAmenitySummaries\Widgets\AmenityStats;
use App\Filament\Resources\ResidenceAmenitySummaries\ResidenceAmenitySummaryResource;
use App\Models\Residence;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListResidenceAmenitySummaries extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ResidenceAmenitySummaryResource::class;

    protected array $topAmenityIds = [];

    protected function getHeaderWidgets(): array
    {
        return [
            AmenityChart::class,
            FacilityChart::class,
            AmenityStats::class,
        ];
    }

    protected function getTableQuery(): Builder
    {
        $this->topAmenityIds = DB::table('residence_amenity')
            ->select('facility_and_amenity_id')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('facility_and_amenity_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('facility_and_amenity_id')
            ->toArray();

        $query = Residence::with(['facilityAndAmenities', 'residenceAmenities']);

        if (auth()->user()->hasRole('Property Management')) {
            $residence = get_residence_by_property_management(auth()->id());

            return $query->where('id', $residence->id);
        }

        return $query;
    }
}
