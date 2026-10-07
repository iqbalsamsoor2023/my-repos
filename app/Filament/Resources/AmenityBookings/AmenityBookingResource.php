<?php

namespace App\Filament\Resources\AmenityBookings;

use App\Filament\Resources\AmenityBookings\Pages\CreateAmenityBooking;
use App\Filament\Resources\AmenityBookings\Pages\EditAmenityBooking;
use App\Filament\Resources\AmenityBookings\Pages\ListAmenityBookings;
use App\Filament\Resources\AmenityBookings\Pages\ViewAmenityBooking;
use App\Filament\Resources\AmenityBookings\Schemas\AmenityBookingForm;
use App\Filament\Resources\AmenityBookings\Schemas\AmenityBookingInfolist;
use App\Filament\Resources\AmenityBookings\Tables\AmenityBookingsTable;
use App\Filament\Resources\AmenityBookings\Widgets\BookingChartOverview;
use App\Models\AmenityBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AmenityBookingResource extends Resource
{
    protected static ?string $model = AmenityBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.amenity_booking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.amenity_bookings');
    }

    public static function form(Schema $schema): Schema
    {
        return AmenityBookingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AmenityBookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AmenityBookingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmenityBookings::route('/'),
            'create' => CreateAmenityBooking::route('/create'),
            'view' => ViewAmenityBooking::route('/{record}'),
            'edit' => EditAmenityBooking::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            BookingChartOverview::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = AmenityBooking::query()
            ->with([
                'unit.residence:id,name,property_management_user_id',
                'user:id,name',
                'amenityBookable:id,facility_and_amenity_id',
                'amenityBookable.facilityAndAmenity:id,name,name_in_thai',
            ]);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('unit.residence', fn ($q) => $q->where('property_management_user_id', $user->id));
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $query->whereHas('unit.residence.propertyManagement', function ($q) use ($user) {
                $q->where('mmb_user_id', $user->id);
            });
        }

        return $query;
    }
}
