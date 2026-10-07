<?php

namespace App\Filament\Pages\DataVisualization;

use App\Filament\Resources\FacilityBookingResource\Widgets\FacilityBookingsChart;
use Filament\Pages\Page;

class FacilityBooking extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.data-visualization.facility-booking';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('menu.data_visualization');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.facility_bookings');
    }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return ! auth()->user()->hasRole('Super Admin');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            FacilityBookingsChart::class,
        ];
    }
}
