<?php

namespace App\Filament\Resources\Visitors\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\LogisticPartner\CategoryType;
use App\Enums\User\RoleType;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\VehicleType;
use App\Enums\Visitor\VisitingArrangementStatus;
use App\Models\LogisticPartner;
use App\Models\Residence;
use App\Models\VisitorLog;
use App\Models\VisitorLogArchive;
use App\Models\VisitorPurpose;
use App\Services\ThailandLocationService;
use App\Services\VisitorLogService;
use App\Support\QueryGuardSupport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class HistoricalVisitorsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->description(__('visitor.historical_visitor_table_description'))
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with([
                    'visitor:id,name,id_type,id_number,contact_no',
                    'visitorCard:id,visitor_card_no',
                    'visitingArrangements:id,visitor_log_id,unit_id,user_id,residence_id,status,estamp_by,estamp_by_type,feedback_remark',
                    'visitingArrangements.unit:id,unit_number,block',
                    'visitingArrangements.user:id,name',
                    'visitingArrangements.residence:id,name',
                    'visitingArrangements.residence.visitorSetting',
                    'preregisterVisitor',
                    'courierLogisticPartner:id,name',
                    'foodDeliveryLogisticPartner:id,name',
                    'residence:id,name,property_management_user_id',
                ]);
            })
            ->columns([
                TextColumn::make('visitor_generated_no')
                    ->label(__('visitor.visitor_no'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('visitor.name')
                    ->label(__('app.name'))
                    ->toggleable(),
                TextColumn::make('resident_names')
                    ->label(__('user.resident_name'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        $residents = $record->visitingArrangements->pluck('user.name')->filter()->unique()->implode(', ');

                        return $residents ?: '-';
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->hidden(auth()->user()->hasRole(['Property Management'])),
                TextColumn::make('unit_numbers')
                    ->label(__('unit.unit_number'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        $units = $record->visitingArrangements
                            ->map(function ($arrangement) {
                                $unit = $arrangement->unit;

                                return $unit?->unit_number ?? $unit?->unit_no;
                            })
                            ->filter()
                            ->unique()
                            ->implode(', ');

                        return $units ?: '-';
                    })
                    ->toggleable(),
                IconColumn::make('is_pre_register')
                    ->label(__('visitor.is_pre_register'))
                    ->boolean(),
                TextColumn::make('temperature')
                    ->label(__('user.temperature'))
                    ->toggleable(),
                TextColumn::make('company_name')
                    ->label(__('app.company_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('passenger_count')
                    ->label(__('visitor.no_of_passenger'))
                    ->toggleable(),
                TextColumn::make('arrival_type')
                    ->label(__('visitor.arrival_type'))
                    ->formatStateUsing(function (string $state): string {
                        return $state == ArrivalType::DRIVE_IN->value ? __(str_replace('_', ' ', ucfirst(strtolower(ArrivalType::DRIVE_IN->name)))) : __(str_replace('_', ' ', ucfirst(strtolower(ArrivalType::WALK_IN->name))));
                    })
                    ->toggleable(),
                TextColumn::make('vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->formatStateUsing(function (string $state): string {
                        $vehicleTypeMappings = [
                            VehicleType::CAR->value => __('vehicle.'.strtolower(VehicleType::CAR->name)),
                            VehicleType::TRUCK->value => __('vehicle.'.strtolower(VehicleType::TRUCK->name)),
                            VehicleType::MOTORBIKE->value => __('vehicle.'.strtolower(VehicleType::MOTORBIKE->name)),
                            VehicleType::VAN->value => __('vehicle.'.strtolower(VehicleType::VAN->name)),
                            VehicleType::TAXI->value => __('vehicle.'.strtolower(VehicleType::TAXI->name)),
                            VehicleType::PICKUP->value => __('vehicle.'.strtolower(VehicleType::PICKUP->name)),
                        ];

                        return $vehicleTypeMappings[$state] ?? '-';
                    })
                    ->toggleable(),
                TextColumn::make('vehicle_plate_no')
                    ->label(__('vehicle.plate_number'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('vehicle_province')
                    ->label(__('vehicle.vehicle_province'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->visitorParking?->vehicle_province
                            ?? self::vehicleInfoValue($record->vehicle_info, ['province', 'vehicle_province', 'vsProvinceOfVehicle', 'vsProvince', 'vehicleProvince'])
                            ?: '-';
                    })
                    ->toggleable(),
                TextColumn::make('vehicle_brand')
                    ->label(__('vehicle.vehicle_brand'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->visitorParking?->vehicle_brand
                            ?? self::vehicleInfoValue($record->vehicle_info, ['vehicle_brand', 'brand', 'vsBrand', 'vehicleBrand'])
                            ?: '-';
                    })
                    ->toggleable(),
                TextColumn::make('vehicle_color')
                    ->label(__('vehicle.vehicle_color'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->visitorParking?->vehicle_color
                            ?? self::vehicleInfoValue($record->vehicle_info, ['vehicle_color', 'color', 'vsColor', 'vehicleColor'])
                            ?: '-';
                    })
                    ->toggleable(),
                TextColumn::make('visitor_purpose')
                    ->label(__('visitor.purpose_of_visit'))
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('courierLogisticPartner.name')
                    ->label(__('parcel.courier'))
                    ->toggleable(),
                TextColumn::make('foodDeliveryLogisticPartner.name')
                    ->label(__('parcel.food_delivery'))
                    ->toggleable(),
                TextColumn::make('visitor.id_number')
                    ->label(__('user.thai_id_or_license_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('arrival_time')
                    ->label(__('visitor.arrival_time'))
                    ->toggleable()
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->arrival_time
                            ? Carbon::parse($record->arrival_time)->format('d-M-y H:i:s')
                            : '-';
                    }),
                TextColumn::make('leave_time')
                    ->label(__('visitor.leave_time'))
                    ->toggleable()
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->leave_time
                            ? Carbon::parse($record->leave_time)->format('d-M-y H:i:s')
                            : '-';
                    }),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->status ? ucfirst($record->status) : '-';
                    })
                    ->toggleable(),
                IconColumn::make('is_allowed')
                    ->label(__('visitor.is_blacklisted_allowed'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('validity_start_date')
                    ->label(__('visitor.duration_start'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->preregisterVisitor?->validity_start_date
                            ? Carbon::parse($record->preregisterVisitor->validity_start_date)->format('d-M-y H:i:s')
                            : '-';
                    })
                    ->toggleable(),
                TextColumn::make('duration_end')
                    ->label(__('visitor.duration_end'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->preregisterVisitor?->validity_end_date
                            ? Carbon::parse($record->preregisterVisitor->validity_end_date)->format('d-M-y H:i:s')
                            : '-';
                    })
                    ->toggleable(),
                TextColumn::make('duration_type')
                    ->label(__('visitor.duration_type'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        if ($record->is_pre_register && $record->preregisterVisitor) {
                            return $record->preregisterVisitor->is_multiple_entry ? 'Limited Time' : 'One Time';
                        }

                        return '-';
                    })
                    ->toggleable(),
                TextColumn::make('visitor_card')
                    ->label(__('visitor.visitor_card'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->visitor_card_id ? 'Yes' : '-';
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return Carbon::parse($record->created_at)->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return Carbon::parse($record->created_at)->format('H:i:s');
                    }),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at_date'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return Carbon::parse($record->updated_at)->format('d-M-y');
                    })
                    ->toggleable(),
                TextColumn::make('updated_at_time')
                    ->label(__('app.updated_at_time'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return Carbon::parse($record->updated_at)->format('H:i:s');
                    })
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('blacklist_remark')
                    ->label(__('visitor.blacklist_remark'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('estamp_status')
                    ->label(__('visitor.estamp_status'))
                    ->getStateUsing(function (VisitorLogArchive $record) {
                        return $record->estamp_status['label'] ?? __('visitor.estamp_status_pending');
                    })
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('location')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('province_id')
                            ->label(__('app.province'))
                            ->placeholder(__('app.all_provinces'))
                            ->options(fn () => ThailandLocationService::getProvinces())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('district_ids', [])),
                        Select::make('district_ids')
                            ->label(__('app.district'))
                            ->placeholder(__('app.all_districts'))
                            ->options(function (callable $get): array {
                                $provinceIds = array_filter((array) $get('province_id'));

                                if (empty($provinceIds)) {
                                    return [];
                                }

                                return ThailandLocationService::getDistrictsByProvinces(array_map('intval', $provinceIds));
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->disabled(fn (callable $get): bool => blank($get('province_id'))),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $provinceIds = array_filter((array) ($data['province_id'] ?? []));
                        $districtIds = array_filter((array) ($data['district_ids'] ?? []));

                        $residenceIds = ThailandLocationService::getResidenceIdsByLocation($provinceIds, $districtIds);

                        return $query
                            ->when(
                                ! empty($residenceIds),
                                fn (Builder $query): Builder => $query->whereIn('residence_id', $residenceIds)
                            )
                            ->when(
                                (empty($residenceIds) && (! empty($provinceIds) || ! empty($districtIds))),
                                fn (Builder $query): Builder => QueryGuardSupport::denyAll($query)
                            );
                    })
                    ->visible($user->hasRole(['Super Admin', 'Property Management Operation Center'])),
                SelectFilter::make('residence')
                    ->label(__('app.mooban_or_residence'))
                    ->options(list_residences())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query): Builder => $query->where('residence_id', $data['value']),
                            );
                    })
                    ->visible($user->hasRole('Super Admin')),
                Filter::make(__('app.unit'))
                    ->schema([
                        TextInput::make('unit')
                            ->label(__('unit.unit')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['unit'],
                                fn (Builder $query): Builder => $query->whereHas('visitingArrangements.unit', function ($q) use ($data) {
                                    $q->where('unit_number', 'LIKE', '%'.$data['unit'].'%');
                                }),
                            );
                    }),
                Filter::make('visitor_generated_no')
                    ->schema([
                        TextInput::make('visitor_generated_no')
                            ->label(__('visitor.visitor_no'))
                            ->placeholder('00000-000000-0000'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $visitorNo = $data['visitor_generated_no'] ?? null;

                        if ($visitorNo && ! self::isCompleteVisitorNo($visitorNo)) {
                            Notification::make()
                                ->id('visitor-no-incomplete')
                                ->title(__('visitor.visitor_no_incomplete_title'))
                                ->body(__('visitor.visitor_no_incomplete_body'))
                                ->warning()
                                ->send();

                            return $query;
                        }

                        if (self::isCompleteVisitorNo($visitorNo)) {
                            if (VisitorLog::where('visitor_generated_no', $visitorNo)->exists()) {
                                Notification::make()
                                    ->id('visitor-no-found-live')
                                    ->title(__('visitor.visitor_no_found_live_title'))
                                    ->body(__('visitor.visitor_no_found_live_body', ['number' => $visitorNo]))
                                    ->danger()
                                    ->send();
                            }
                        }

                        return $query->when(
                            self::isCompleteVisitorNo($visitorNo),
                            fn (Builder $query): Builder => $query->where('visitor_generated_no', $visitorNo)
                        );
                    }),
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['name'],
                                fn (Builder $query): Builder => $query->whereHas('visitor', function ($q) use ($data) {
                                    $q->where('name', 'LIKE', '%'.$data['name'].'%');
                                }),
                            );
                    }),
                TernaryFilter::make('is_pre_register')
                    ->label(__('visitor.is_pre_register'))
                    ->trueLabel(__('app.active'))
                    ->falseLabel(__('app.inactive'))
                    ->queries(
                        true: fn ($query) => $query->where('is_pre_register', true),
                        false: fn ($query) => $query->where('is_pre_register', false),
                    ),
                SelectFilter::make('arrival_type')
                    ->label(__('visitor.arrival_type'))
                    ->options(
                        collect(ArrivalType::cases())->mapWithKeys(fn ($status) => [
                            $status->value => $status->getLabel(),
                        ])->toArray()
                    ),
                SelectFilter::make('vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->options(
                        collect(VehicleType::cases())->mapWithKeys(fn ($type) => [
                            $type->value => $type->getLabel(),
                        ])->toArray()
                    ),
                Filter::make('vehicle_plate_no')
                    ->schema([
                        TextInput::make('vehicle_plate_no')
                            ->label(__('vehicle.vehicle_plate_number')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['vehicle_plate_no'],
                                fn (Builder $query): Builder => $query->where('vehicle_plate_no', 'LIKE', '%'.$data['vehicle_plate_no'].'%'),
                            );
                    }),
                SelectFilter::make('visitor_purpose')
                    ->label(__('visitor.purpose_of_visit'))
                    ->options(function () use ($user) {
                        $defaultOptions = [
                            'Receive/Delivery' => __('visitor.receive_or_delivery'),
                            'Drop Off/Pick Up' => __('visitor.dropoff_or_pickup'),
                            'Contractor/Worker' => __('visitor.contractor_or_worker'),
                            'Visitor Parking' => __('visitor.visitor_parking'),
                            'VIP' => __('visitor.vip'),
                        ];

                        if ($user->hasRole('Property Management')) {
                            $residence = Residence::where('property_management_user_id', $user->id)->first();
                            $visitorPurposes = $residence
                                ? VisitorPurpose::where('residence_id', $residence->id)->pluck('purpose', 'purpose')->toArray()
                                : [];

                            return array_merge($defaultOptions, $visitorPurposes);
                        } else {
                            return array_merge($defaultOptions, ['Other' => __('app.other')]);
                        }
                    })
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $value = $data['value'];
                            $translationKey = 'visitor.'.Str::snake(str_replace('/', '_', strtolower($value)));
                            $thaiValue = __($translationKey, [], 'th');
                            $englishValue = $value;

                            $query->where(function ($q) use ($englishValue, $thaiValue) {
                                $q->where('visitor_purpose', 'like', "%{$englishValue}%");

                                if ($thaiValue !== $englishValue && ! empty($thaiValue) && strpos($thaiValue, '.') === false) {
                                    $q->orWhere('visitor_purpose', 'like', "%{$thaiValue}%");
                                }
                            });
                        }
                    }),
                SelectFilter::make('courier_logistic_partner_id')
                    ->label(__('parcel.courier'))
                    ->options(function () {
                        return LogisticPartner::query()
                            ->where('category', '=', CategoryType::Courier->value)
                            ->orderBy('name', 'asc')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable(),
                SelectFilter::make('food_delivery_logistic_partner_id')
                    ->label(__('visitor.food_delivery'))
                    ->options(function () {
                        return LogisticPartner::query()
                            ->where('category', '=', CategoryType::FoodDelivery->value)
                            ->orderBy('name', 'asc')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable(),
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options([
                        'arrive' => 'Arrive',
                        'depart' => 'Depart',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'arrive' => $query->whereNotNull('arrival_time')->whereNull('leave_time'),
                            'depart' => $query->whereNotNull('arrival_time')->whereNotNull('leave_time'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('duration_type')
                    ->label(__('visitor.duration_type'))
                    ->options([
                        '1' => 'Limited Time',
                        '0' => 'One Time',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->where('is_pre_register', true)
                            ->whereHas('preregisterVisitor', function ($q) use ($data) {
                                $q->where('is_multiple_entry', $data['value'] === '1');
                            });
                    }),
                SelectFilter::make('estamp_status')
                    ->label(__('visitor.estamp_status'))
                    ->options([
                        'MY_VISITOR' => 'My Visitor',
                        'NOT_MY_VISITOR' => 'Not My Visitor',
                        'CANCEL_BY_SG' => 'Cancel by SG',
                        'STAMP_BY_PM' => 'Stamp by PM',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('visitingArrangements', function ($q) use ($data) {
                            $statusValue = match ($data['value']) {
                                'MY_VISITOR' => VisitingArrangementStatus::MY_VISITOR->value,
                                'NOT_MY_VISITOR' => VisitingArrangementStatus::NOT_MY_VISITOR->value,
                                'CANCEL_BY_SG' => VisitingArrangementStatus::CANCEL_BY_SG->value,
                                'STAMP_BY_PM' => VisitingArrangementStatus::STAMP_BY_PM->value,
                                default => null,
                            };

                            if ($statusValue !== null) {
                                $q->where('status', $statusValue);
                            }
                        });
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $createdFrom = $data['created_from'] ?? null;
                        $createdUntil = $data['created_until'] ?? null;

                        $fromDate = $createdFrom ? Carbon::parse($createdFrom) : null;
                        $untilDate = $createdUntil ? Carbon::parse($createdUntil) : null;

                        $recentThreshold = Carbon::now()->subDays(30)->startOfDay();

                        if (($fromDate && $fromDate->greaterThanOrEqualTo($recentThreshold))
                            || ($untilDate && $untilDate->greaterThanOrEqualTo($recentThreshold))
                        ) {
                            Notification::make()
                                ->id('visitor-date-range-within-live')
                                ->title(__('visitor.historical_range_within_live_title'))
                                ->body(__('visitor.historical_range_within_live_body'))
                                ->warning()
                                ->actions([
                                    Action::make('view-live')
                                        ->label(__('visitor.view_live_visitors'))
                                        ->url(route('filament.admin.resources.visitors.index'))
                                        ->button()
                                        ->openUrlInNewTab(true),
                                ])
                                ->send();
                        }

                        if (! Schema::hasColumn('visitor_logs_archive', 'created_date')) {
                            return $query
                                ->when(
                                    $fromDate,
                                    fn (Builder $query, $date): Builder => $query->where('created_at', '>=', $date->startOfDay()),
                                )
                                ->when(
                                    $untilDate,
                                    fn (Builder $query, $date): Builder => $query->where('created_at', '<=', $date->endOfDay()),
                                );
                        }

                        return $query
                            ->when(
                                $fromDate,
                                fn (Builder $query, Carbon $date): Builder => $query
                                    ->where('created_date', '>=', $date->toDateString())
                                    ->where('created_at', '>=', $date->copy()->startOfDay()),
                            )
                            ->when(
                                $untilDate,
                                fn (Builder $query, Carbon $date): Builder => $query
                                    ->where('created_date', '<=', $date->toDateString())
                                    ->where('created_at', '<=', $date->copy()->endOfDay()),
                            );
                    }),
                Filter::make('updated_at')
                    ->schema([
                        DatePicker::make('updated_from')
                            ->label(__('app.updated_from')),
                
                        DatePicker::make('updated_until')
                            ->label(__('app.updated_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $updatedFrom = $data['updated_from'] ?? null;
                        $updatedUntil = $data['updated_until'] ?? null;
                
                        $fromUpdatedDate = $updatedFrom
                            ? Carbon::parse($updatedFrom)
                            : null;
                
                        $untilUpdatedDate = $updatedUntil
                            ? Carbon::parse($updatedUntil)
                            : null;
                
                        $recentThreshold = Carbon::now()->subDays(30)->startOfDay();
                
                        // Warn when the selected range overlaps the last 30 days.
                        $rangeOverlapsRecent =
                            ($fromUpdatedDate && $fromUpdatedDate->lessThanOrEqualTo(Carbon::now())
                                && (!$untilUpdatedDate || $untilUpdatedDate->greaterThanOrEqualTo($recentThreshold)))
                            ||
                            (!$fromUpdatedDate && $untilUpdatedDate
                                && $untilUpdatedDate->greaterThanOrEqualTo($recentThreshold));
                
                        if ($rangeOverlapsRecent) {
                            Notification::make()
                                ->id('visitor-date-range-within-live')
                                ->title(__('visitor.historical_range_within_live_title'))
                                ->body(__('visitor.historical_range_within_live_body'))
                                ->warning()
                                ->actions([
                                    Action::make('view-live')
                                        ->label(__('visitor.view_live_visitors'))
                                        ->url(route('filament.admin.resources.visitors.index'))
                                        ->button()
                                        ->openUrlInNewTab(true),
                                ])
                                ->send();
                        }
                
                        return $query
                            ->when(
                                $fromUpdatedDate,
                                fn (Builder $query, Carbon $date): Builder => $query
                                    ->where('updated_at', '>=', $date->copy()->startOfDay()),
                            )
                            ->when(
                                $untilUpdatedDate,
                                fn (Builder $query, Carbon $date): Builder => $query
                                    ->where('updated_at', '<=', $date->copy()->endOfDay()),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('app.view'))
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn ($record) => route('filament.admin.resources.visitors.view', ['record' => $record->id])),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->paginationPageOptions([10, 20, 30, 50])
            ->toolbarActions([
                BulkAction::make('export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->modalDescription(__('visitor.export_limit_description'))
                    ->action(function (Component $livewire) {
                        $ids = $livewire->getSelectedTableRecords()->pluck('id')->toArray();

                        if (count($ids) > 1000) {
                            Notification::make()
                                ->title(__('visitor.export_limit_exceeded_title'))
                                ->body('You can export a maximum of 1,000 records at a time. Please reduce your selection.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $data = [
                            'language' => app()->getLocale(),
                            'project' => 'MMB2',
                            'user_id' => auth()->user()->id,
                            'format' => 'xlsx',
                            'data_type' => 'historical',
                            'ids' => $ids,
                        ];

                        $repository = new VisitorLogService;
                        $repository->export($data);

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported visitor records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        $livewire->redirect(request()->header('Referer'));
                    })
                    ->hidden($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])),
            ]);
    }

    private static function isCompleteVisitorNo(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return (bool) preg_match('/^\d{5}-\d{6}-\d{4}$/', $value);
    }

    private static function vehicleInfoValue($vehicleInfo, array $keys): ?string
    {
        if (is_string($vehicleInfo)) {
            $decoded = json_decode($vehicleInfo, true);
            $vehicleInfo = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($vehicleInfo)) {
            return null;
        }

        foreach ($keys as $key) {
            $value = $vehicleInfo[$key] ?? null;

            if (! empty($value)) {
                return $value;
            }
        }

        return null;
    }
}
