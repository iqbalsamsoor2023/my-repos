<?php

namespace App\Filament\Resources\AmenityBookings\Tables;

use App\Actions\Audit\CreateAuditAction;
use Filament\Tables\Columns\TextColumn;
use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use App\Exports\AmenityBookingExport;
use App\Filament\Resources\AmenityBookings\Pages\ListAmenityBookings;
use App\Models\AmenityBooking;
use App\Models\ResidenceAmenity;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class AmenityBookingsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('ref_no')
                    ->label(__('Ref. No'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(Model $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->hidden($user->hasRole(['Property Management'])),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('app.resident'))
                    ->toggleable(),
                TextColumn::make('amenity_bookingable_type')
                    ->label(__('app.amenity'))
                    ->getStateUsing(function (Model $record) {
                        return $record->amenity_bookable_type === ResidenceAmenity::class
                            ? $record?->amenityBookable?->facilityAndAmenity?->name
                            : $record?->amenityBookable?->name;
                    })
                    ->toggleable()
                    ->description(fn(Model $record) => $record->amenity_bookable_type === ResidenceAmenity::class ? $record?->amenityBookable?->facilityAndAmenity?->name_in_thai : $record?->amenityBookable?->name_in_thai),
                TextColumn::make('start_at')
                    ->label(__('app.booking_date'))
                    ->toggleable()
                    ->getStateUsing(function (Model $record) {
                        return date('l, d-M-y', strtotime($record->start_at));
                    }),
                TextColumn::make('booking_time')
                    ->label(__('app.booking_time'))
                    ->getStateUsing(function (Model $record) {
                        $startTime = Carbon::parse($record->getOriginal('start_at'))->format('H:i');
                        $endTime = Carbon::parse($record->getOriginal('end_at'))->format('H:i');

                        return $startTime . '-' . $endTime;
                    })
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->formatStateUsing(function (?int $state): string {
                        $enum = AmenityBookingStatusEnum::tryFrom($state);

                        return $enum?->getLabel() ?? '-';
                    })
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (Model $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (Model $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(fn(Component $livewire): bool => $livewire instanceof ListAmenityBookings && auth()->user()->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                Filter::make('unit_number')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['unit_number'])) {
                            return $query->whereHas('unit', function ($q) use ($data) {
                                return $q->where('unit_number', 'LIKE', '%' . $data['unit_number'] . '%');
                            });
                        }
                    }),
                Filter::make('resident')
                    ->schema([
                        TextInput::make('resident')
                            ->label(__('app.resident')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['resident'])) {
                            return $query->whereHas('user', function ($q) use ($data) {
                                return $q->where('name', 'LIKE', '%' . $data['resident'] . '%');
                            });
                        }
                    }),
                Filter::make('ref_no')
                    ->schema([
                        TextInput::make('ref_no')
                            ->label(__('Ref No')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['ref_no'])) {
                            return $query->where('ref_no', $data['ref_no']);
                        }
                    }),
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options(AmenityBookingStatusEnum::options()),
                Filter::make('start_at')
                    ->schema([
                        DatePicker::make('booking_from')
                            ->label(__('Booking From')),
                        DatePicker::make('booking_until')
                            ->label(__('Booking Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['booking_from'],
                                fn($query, $date) =>
                                $query->where('start_at', '>=', Carbon::parse($date)->startOfDay())
                            )
                            ->when(
                                $data['booking_until'],
                                fn($query, $date) =>
                                $query->where('start_at', '<=', Carbon::parse($date)->endOfDay())
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Amenity-Booking-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        $columns = collect($livewire->getTable()->getColumns())
                            ->filter(fn($col) => $col->isVisible())
                            ->map(fn($col) => $col->getName())
                            ->values()
                            ->toArray();
                        $fileName = $livewire->mountedActions[0]['data']['file_name'] . '.xlsx';
                        $ids = $livewire->getSelectedTableRecords()->pluck('id')->toArray();
                        $amenityBookings = AmenityBooking::whereIn('id', $ids)->latest('id')->get();

                        $audit = new CreateAuditAction();
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported amnity bookings records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new AmenityBookingExport($amenityBookings, $columns), $fileName);
                    }),
            ]);
    }
}
