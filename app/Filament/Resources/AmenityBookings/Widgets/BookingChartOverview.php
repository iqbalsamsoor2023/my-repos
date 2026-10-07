<?php

namespace App\Filament\Resources\AmenityBookings\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BookingChartOverview extends ChartWidget
{
    protected ?string $heading = 'Amenity Bookings Overview (Last 12 Months)';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $startDate = Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Query for ResidenceAmenityOption
        $optionBookings = DB::table('amenity_bookings')
            ->join('residence_amenity_options', 'amenity_bookings.amenity_bookable_id', '=', 'residence_amenity_options.id')
            ->selectRaw("
                CONCAT(residence_amenity_options.name, ' / ', residence_amenity_options.name_in_thai) AS amenity_name,
                COUNT(*) AS count
            ")
            ->where('amenity_bookable_type', 'App\Models\ResidenceAmenityOption')
            ->whereBetween('start_at', [$startDate, $endDate])
            ->groupBy('amenity_name')
            ->get();

        // Query for ResidenceAmenity
        $residenceBookings = DB::table('amenity_bookings')
            ->join('residence_amenity', 'amenity_bookings.amenity_bookable_id', '=', 'residence_amenity.id')
            ->join('facilities_and_amenities', 'residence_amenity.facility_and_amenity_id', '=', 'facilities_and_amenities.id')
            ->selectRaw("
                CONCAT(facilities_and_amenities.name, ' / ', facilities_and_amenities.name_in_thai) AS amenity_name,
                COUNT(*) AS count
            ")
            ->where('amenity_bookable_type', 'App\Models\ResidenceAmenity')
            ->whereBetween('start_at', [$startDate, $endDate])
            ->groupBy('amenity_name')
            ->get();

        $merged = $optionBookings->merge($residenceBookings);

        // Group by amenity_name and sum counts if the same name appears in both types
        $grouped = $merged->groupBy('amenity_name')->map(function ($group) {
            return $group->sum('count');
        });

        $labels = $grouped->keys()->toArray();
        $data = $grouped->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Bookings (Last 12 Months)',
                    'data' => $data,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
