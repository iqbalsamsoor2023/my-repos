<?php

namespace App\Filament\Resources\AmenityBookings\Schemas;

use App\Models\AmenityBooking;
use App\Models\ResidenceAmenity;
use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AmenityBookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.amenity_booking'))
                    ->description(__('app.amenity_booking_detail'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('residence')
                                    ->label(__('app.mooban_or_residence'))
                                    ->getStateUsing(function ($record) {
                                        $locale = app()->getLocale();
                                    
                                        return $locale === 'th'
                                            ? ($record?->unit?->residence?->name_th ?? '-')
                                            : ($record?->unit->residence?->name ?? '-');
                                    })
                                    ->inlineLabel()
                                    ->color('primary'),
                                TextEntry::make('unit_id')
                                    ->label(__('unit.unit_number'))
                                    ->inlineLabel()
                                    ->getStateUsing(function (AmenityBooking $record) {
                                        return optional($record?->unit)->unit_number;
                                    })
                                    ->color('primary'),
                                TextEntry::make('user_id')
                                    ->label(__('app.resident'))
                                    ->inlineLabel()
                                    ->getStateUsing(function (AmenityBooking $record) {
                                        return optional($record?->user)->name;
                                    })
                                    ->color('primary'),
                                TextEntry::make('amenity_id')
                                    ->label(__('app.amenity'))
                                    ->inlineLabel()
                                    ->getStateUsing(function ($record) {
                                        if (! $record->amenityBookable) {
                                            return '-';
                                        }
                                
                                        // ResidenceAmenity
                                        if ($record->amenityBookable instanceof \App\Models\ResidenceAmenity) {
                                            $facility = $record->amenityBookable->facilityAndAmenity;
                                
                                            return $facility
                                                ? "{$facility->name} ({$facility->name_in_thai})"
                                                : '-';
                                        }
                                
                                        // ResidenceAmenityOption
                                        if ($record->amenityBookable instanceof \App\Models\ResidenceAmenityOption) {
                                            return "{$record->amenityBookable->name} ({$record->amenityBookable->name_in_thai})";
                                        }
                                
                                        return '-';
                                    })
                                    ->color('primary'),
                                TextEntry::make('amenity_sub_item_id')
                                    ->label(__('app.amenity_sub_item'))
                                    ->inlineLabel()
                                    ->getStateUsing(function ($state) {
                                        $option = \App\Models\ResidenceAmenityOption::find($state);
                                
                                        if (! $option) {
                                            return '-';
                                        }
                                
                                        return "{$option->name} ({$option->name_in_thai})";
                                    })
                                    ->visible(fn ($record) => ! empty($record->amenity_sub_item_id))
                                    ->color('primary'),
                                TextEntry::make('booking_date')
                                    ->label(__('app.booking_date'))
                                    ->inlineLabel()
                                    ->getStateUsing(fn ($record) =>
                                        $record->start_at
                                            ? \Carbon\Carbon::parse($record->start_at)->format('d M Y')
                                            : '-'
                                    )
                                    ->color('primary'),
                                TextEntry::make('time_range')
                                    ->label(__('app.select_time_range'))
                                    ->inlineLabel()
                                    ->getStateUsing(function ($record) {
                                        if (! $record->start_at || ! $record->end_at) {
                                            return '-';
                                        }
                                
                                        $start = Carbon::parse($record->start_at);
                                        $end   = Carbon::parse($record->end_at);

                                        return $start->format('g:i A') . ' - ' . $end->format('g:i A');
                                    })
                                    ->color('primary')
                            ]),
                    ])
            ]);
    }
}
