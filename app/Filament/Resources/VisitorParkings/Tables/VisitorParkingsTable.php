<?php

namespace App\Filament\Resources\VisitorParkings\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\Parking\DiscountType;
use App\Enums\Visitor\VehicleType;
use App\Enums\Visitor\VisitorParkingChartered;
use App\Exports\VisitorParkingExport;
use App\Models\Calculation;
use App\Models\VisitorParking;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class VisitorParkingsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                // ViewColumn::make('parking_rate')
                //     ->label(__('Rate (THB)'))
                //     ->view('tables.columns.visitor-parking.parking-rate'),
                TextColumn::make('visitorLog.visitor_generated_no')
                    ->label(__('visitor.visitor_no'))
                    ->toggleable(),
                TextColumn::make('visitorLog.visitor.name')
                    ->label(__('app.name'))
                    ->toggleable(),
                TextColumn::make('visitorLog.vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->formatStateUsing(function (string $state, Component $livewire): string {

                        $vehicleTypeMappings = [
                            VehicleType::CAR->value => __('vehicle.' . strtolower(VehicleType::CAR->name)),
                            VehicleType::TRUCK->value => __('vehicle.' . strtolower(VehicleType::TRUCK->name)),
                            VehicleType::MOTORBIKE->value => __('vehicle.' . strtolower(VehicleType::MOTORBIKE->name)),
                            VehicleType::VAN->value => __('vehicle.' . strtolower(VehicleType::VAN->name)),
                            VehicleType::TAXI->value => __('vehicle.' . strtolower(VehicleType::TAXI->name)),
                            VehicleType::PICKUP->value => __('vehicle.' . strtolower(VehicleType::PICKUP->name)),
                        ];

                        $vehicleType = $vehicleTypeMappings[$state] ?? '-';

                        return $vehicleType;
                    })
                    ->toggleable(),
                TextColumn::make('visitorLog.vehicle_plate_no')
                    ->label(__('vehicle.vehicle_plate_number'))
                    ->toggleable(),
                TextColumn::make('unit_to_visit')
                    ->label(__('unit.unit_to_visit'))
                    ->getStateUsing(function (VisitorParking $record) {
                        $unit_numbers = [];

                        if (isset($record->visitorLog->visitingArrangements)) {
                            foreach ($record->visitorLog->visitingArrangements as $visitingArrangement) {
                                $unit_numbers[] = ucfirst(data_get($visitingArrangement, 'unit.unit_number', '-'));
                            }
                        }

                        return array_unique($unit_numbers);
                    })
                    ->toggleable(),
                TextColumn::make('arrival_date')
                    ->label(__('visitor.arrived_at_date'))
                    ->getStateUsing(function (VisitorParking $record) {
                        return date('d-M-y', strtotime($record->visitorLog->arrival_time));
                    })
                    ->toggleable(),
                TextColumn::make('visitorLog.arrival_time')
                    ->label(__('visitor.arrived_at_time'))
                    ->time()
                    ->toggleable(),
                TextColumn::make('departure_date')
                    ->label(__('visitor.departed_at_date'))
                    ->toggleable()
                    ->getStateUsing(function (VisitorParking $record) {
                        if (empty($record->visitorLog->leave_time) == false) {
                            return date('d-M-y', strtotime($record->visitorLog->leave_time));
                        }

                        return '-';
                    }),
                TextColumn::make('visitorLog.leave_time')
                    ->label(__('visitor.departed_at_time'))
                    ->toggleable()
                    ->getStateUsing(function (VisitorParking $record) {
                        if (empty($record->visitorLog->leave_time) == false) {
                            return date('H:i:s', strtotime($record->visitorLog->leave_time));
                        }

                        return '-';
                    }),
                TextColumn::make('parking_hour')
                    ->label(__('visitor.parking_hour'))
                    ->getStateUsing(function (VisitorParking $record) {
                        $freeParkingInMinutes = data_get($record->calculation_records, 'free_parking_minutes');

                        // 1st Step
                        $arrivalTime = new Carbon($record->visitorLog->arrival_time);
                        $departTime = is_null($record->visitorLog->leave_time) ? Carbon::now() : new Carbon($record->visitorLog->leave_time);
                        $freeParkingInMinutes = ((new Carbon($freeParkingInMinutes))->hour) * 60 + (new Carbon($freeParkingInMinutes))->minute;

                        $timeDifference = $departTime->diff($arrivalTime);
                        $hours = ($timeDifference->days * 24) + $timeDifference->h;
                        $form = $hours * 60 + $timeDifference->i;
                        $hours = floor($form / 60);
                        $minutes = $form % 60;
                        $seconds = $timeDifference->s;

                        // Handle if negative value
                        if ($hours < 0 || $minutes < 0 || $seconds < 0) {
                            return 0 . __(' Hours');
                        } else {
                            return "$hours " . __('Hours') . " $minutes " . __('Minutes') . " $seconds " . __('Seconds');
                        }
                    })
                    ->toggleable(),
                IconColumn::make('is_free_parking')
                    ->label(__('visitor.is_free_parking'))
                    ->boolean()
                    ->getStateUsing(function (VisitorParking $record) {
                        if (isset($record->calculation->parking)) {
                            $isFreeParking = $record->calculation->parking->type;
                        } else {
                            $calculation = Calculation::withTrashed()->where('id', $record->calculation_id)->first();
                            $isFreeParking = $calculation->parking->type;
                        }

                        return $isFreeParking === 1 ? true : false;
                    })
                    ->toggleable(),
                IconColumn::make('is_chartered')
                    ->label(__('visitor.is_chartered'))
                    ->boolean()
                    ->getStateUsing(function (VisitorParking $record): bool {
                        $visitorLog = $record->visitorLog;
                
                        if (! $visitorLog) {
                            return false;
                        }
                
                        $charteredDuration = data_get(
                            $record->calculation_records,
                            'chartered_duration'
                        );
                
                        if (
                            empty($charteredDuration) ||
                            $charteredDuration === '00:00:00'
                        ) {
                            return false;
                        }
                
                        $arrivalTime = Carbon::parse($visitorLog->arrival_time);
                
                        $departureTime = $visitorLog->leave_time
                            ? Carbon::parse($visitorLog->leave_time)
                            : now();
                
                        $visitorDurationInMinutes = $departureTime->diffInMinutes(
                            $arrivalTime,
                            true
                        );
                
                        $charteredCarbon = Carbon::parse($charteredDuration);
                
                        $charteredDurationInMinutes =
                            ($charteredCarbon->hour * 60)
                            + $charteredCarbon->minute;
                
                        return $visitorDurationInMinutes >= $charteredDurationInMinutes;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('is_penalty')
                    ->label(__('visitor.is_penalty'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('is_stamp')
                    ->label(__('visitor.is_stamp'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('calculation.parking.discount_type')
                    ->label(__('visitor.discount_type'))
                    ->getStateUsing(function (VisitorParking $record) {
                        if (! isset($record->calculation_records['parking'])) {
                            return 'No parking data';
                        }

                        $discount_type = $record->calculation_records['parking']['discount_type'];

                        if ($discount_type == DiscountType::NO_DISCOUNT_COUPON->value) {
                            return str_replace('_', ' ', Str::title(DiscountType::NO_DISCOUNT_COUPON->name));
                        } elseif ($discount_type == DiscountType::PRICE->value) {
                            return __('visitor.' . strtolower(DiscountType::PRICE->name));
                        } elseif ($discount_type == DiscountType::TIME->value) {
                            return __('visitor.' . strtolower(DiscountType::TIME->name));
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                SpatieMediaLibraryImageColumn::make('voucher_image')
                    ->label(__('visitor.voucher_image'))
                    ->collection('voucher_image'),
                ViewColumn::make('discount_value')
                    ->label(__('visitor.coupon_discount_thb_or_hour'))
                    ->view('tables.columns.visitor-parking.discount-value')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount_paid')
                    ->label(__('visitor.paid_amount_thb'))
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('change')
                    ->label(__('visitor.change_amount_thb'))
                    ->getStateUsing(function (VisitorParking $record) {
                        $amountToPay = data_get($record, 'amount_to_pay');
                        $amountPaid = data_get($record, 'amount_paid');

                        return $amountPaid - $amountToPay;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('amount_to_pay')
                    ->label(__('visitor.total_parking_fee_thb'))
                    ->getStateUsing(function (VisitorParking $record) {
                        $amountToPay = data_get($record, 'amount_to_pay');
                        if ($amountToPay < 0) {
                            return 0;
                        } else {
                            return $amountToPay;
                        }
                    })
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (VisitorParking $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (VisitorParking $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
                ColumnGroup::make(__('app.updated_at'), [
                    TextColumn::make('updated_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (VisitorParking $record) {
                            return $record->updated_at->format('d-M-y');
                        }),
                    TextColumn::make('updated_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (VisitorParking $record) {
                            return $record->updated_at->format('H:i:s');
                        }),
                ]),
                // ViewColumn::make('parking_hours')
                //     ->view('tables.columns.visitor-parking.parking-hour'),

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
                                fn(Builder $query): Builder => $query->whereHas('calculation.parking.residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible($user->hasRole(['Super Admin', 'Property Management Operation Center'])),
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['name'],
                                fn(Builder $query): Builder => $query->whereHas('visitorLog.visitor', function ($q) use ($data) {
                                    return $q->where('name', 'LIKE', '%' . $data['name'] . '%');
                                }),
                            );
                    }),
                SelectFilter::make('is_chartered')
                    ->label(__('visitor.is_chartered'))
                    ->options([
                        VisitorParkingChartered::YES->value => 'Yes',
                        VisitorParkingChartered::NO->value => 'No',
                    ])
                    ->query(function (Builder $query, array $data) {
                
                        $value = (int) ($data['value'] ?? -1);
                
                        if ($value === -1) {
                            return $query;
                        }
                
                        $durationSql = "
                            ABS(
                                TIMESTAMPDIFF(
                                    MINUTE,
                                    visitor_logs.arrival_time,
                                    COALESCE(visitor_logs.leave_time, NOW())
                                )
                            )
                        ";
                
                        $charteredSql = "
                            (
                                TIME_TO_SEC(
                                    JSON_UNQUOTE(
                                        JSON_EXTRACT(
                                            visitor_parkings.calculation_records,
                                            '$.chartered_duration'
                                        )
                                    )
                                ) / 60
                            )
                        ";
                
                        /*
                         * YES
                         */
                        if ($value === VisitorParkingChartered::YES->value) {
                            return $query
                                ->whereHas('visitorLog')
                                ->whereRaw("
                                    JSON_UNQUOTE(
                                        JSON_EXTRACT(
                                            visitor_parkings.calculation_records,
                                            '$.chartered_duration'
                                        )
                                    ) IS NOT NULL
                                ")
                                ->whereRaw("
                                    JSON_UNQUOTE(
                                        JSON_EXTRACT(
                                            visitor_parkings.calculation_records,
                                            '$.chartered_duration'
                                        )
                                    ) != '00:00:00'
                                ")
                                ->whereHas('visitorLog', function (Builder $q) use (
                                    $durationSql,
                                    $charteredSql
                                ) {
                                    $q->whereRaw("
                                        {$durationSql} >= {$charteredSql}
                                    ");
                                });
                        }
                
                        /*
                         * NO
                         */
                        return $query->where(function (Builder $q) use (
                            $durationSql,
                            $charteredSql
                        ) {
                
                            // No visitor log
                            $q->whereDoesntHave('visitorLog');
                
                            // No chartered duration
                            $q->orWhereRaw("
                                JSON_UNQUOTE(
                                    JSON_EXTRACT(
                                        visitor_parkings.calculation_records,
                                        '$.chartered_duration'
                                    )
                                ) IS NULL
                            ");
                
                            // chartered_duration = 00:00:00
                            $q->orWhereRaw("
                                JSON_UNQUOTE(
                                    JSON_EXTRACT(
                                        visitor_parkings.calculation_records,
                                        '$.chartered_duration'
                                    )
                                ) = '00:00:00'
                            ");
                
                            // Visitor duration < chartered duration
                            $q->orWhereHas('visitorLog', function (Builder $q) use (
                                $durationSql,
                                $charteredSql
                            ) {
                                $q->whereRaw("
                                    {$charteredSql} > 0
                                ");
                
                                $q->whereRaw("
                                    {$durationSql} < {$charteredSql}
                                ");
                            });
                        });
                    }),
                SelectFilter::make('vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->options([
                        VehicleType::CAR->value => __('vehicle.' . strtolower(VehicleType::CAR->name)),
                        VehicleType::TRUCK->value => __('vehicle.' . strtolower(VehicleType::TRUCK->name)),
                        VehicleType::MOTORBIKE->value => __('vehicle.' . strtolower(VehicleType::MOTORBIKE->name)),
                        VehicleType::VAN->value => __('vehicle.' . strtolower(VehicleType::VAN->name)),
                        VehicleType::TAXI->value => __('vehicle.' . strtolower(VehicleType::TAXI->name)),
                        VehicleType::PICKUP->value => __('vehicle.' . strtolower(VehicleType::PICKUP->name)),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('visitorLog', function ($q) use ($data) {
                                    return $q->where('vehicle_type', $data['value']);
                                }),
                            );
                    }),
                Filter::make('vehicle_plate_no')
                    ->schema([
                        TextInput::make('vehicle_plate_no')
                            ->label(__('vehicle.vehicle_plate_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['vehicle_plate_no'])) {
                            return $query->whereHas('visitorLog', function ($q) use ($data) {
                                return $q->where('vehicle_plate_no', $data['vehicle_plate_no']);
                            });
                        }
                    }),
                TernaryFilter::make('is_penalty')->label(__('visitor.is_penalty')),
                TernaryFilter::make('is_stamp')->label(__('visitor.is_stamp')),
                SelectFilter::make('discount_type')
                    ->label(__('visitor.discount_type'))
                    ->options([
                        DiscountType::NO_DISCOUNT_COUPON->value => str_replace('_', ' ', Str::title(DiscountType::NO_DISCOUNT_COUPON->name)),
                        DiscountType::PRICE->value => __('visitor.' . strtolower(DiscountType::PRICE->name)),
                        DiscountType::TIME->value => __('visitor.' . strtolower(DiscountType::TIME->name)),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('calculation.parking', function ($q) use ($data) {
                                    return $q->where('discount_type', $data['value']);
                                }),
                            );
                    }),
                Filter::make('unit_number')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['unit_number'])) {
                            return $query->whereHas('visitorLog.visitingArrangements.unit', function ($q) use ($data) {
                                $q->where('unit_number', 'LIKE', '%' . $data['unit_number'] . '%');
                            });
                        }
                    }),
                Filter::make('start_at')
                    ->schema([
                        DatePicker::make('start_from')
                            ->label(__('app.start_from')),
                        DatePicker::make('start_until')
                            ->label(__('app.start_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Visitor-Parking-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (array $data, Collection $records, Component $livewire) use ($user) {

                        // Visible table columns
                        $columns = collect($livewire->getTable()->getColumns())
                            ->filter(fn($column) => $column->isVisible())
                            ->map(fn($column) => $column->getName())
                            ->values()
                            ->all();

                        if (! in_array('voucher_image', $columns)) {
                            $columns[] = 'voucher_image';
                        }

                        // bulk action form data
                        $fileName = ($data['file_name'] ?? 'Visitor-Parking-Report') . '.xlsx';

                        // Selected records
                        $visitorParkings = $livewire->getSelectedTableRecords();

                        $visitorParkings = VisitorParking::with([
                            'visitorLog.visitor',
                            'visitorLog.visitingArrangements.unit',
                            'calculation.parking',
                        ])
                            ->whereIn('id', $visitorParkings->pluck('id'))
                            ->latest('id')
                            ->get();

                        (new CreateAuditAction)->execute(new Request([
                            'user_type' => get_class($user),
                            'user_id' => $user->id,
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported visitor parking records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(
                            new VisitorParkingExport($visitorParkings, $columns),
                            $fileName
                        );
                    }),
                DeleteBulkAction::make(),
            ]);
    }
}
