<?php

namespace App\Filament\Resources\Parcels\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\LogisticPartner\CategoryType;
use App\Enums\Parcel\ParcelStatus;
use App\Enums\Parcel\PickupType;
use App\Enums\Residence\LocationTagType;
use App\Enums\Residence\MoobanType;
use App\Exports\ParcelExport;
use App\Filament\Resources\Parcels\Pages\ListParcels;
use App\Filament\Resources\Units\RelationManagers\ParcelsRelationManager;
use App\Models\Erp\ThailandDistrict;
use App\Models\Erp\ThailandProvince;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\ErpLocationTag;
use App\Models\LogisticPartner;
use App\Models\Parcel;
use App\Models\UnitUser;
use App\Services\FilamentExport\FilamentExportBulkAction;
use App\Services\ThailandLocationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class ParcelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(Parcel $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->hidden(fn(Component $livewire): bool => $livewire instanceof ParcelsRelationManager),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->sortable()
                    ->toggleable()
                    ->hidden(fn(Component $livewire): bool => $livewire instanceof ParcelsRelationManager),
                TextColumn::make('parcel_generated_no')
                    ->label(__('parcel.parcel_id'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('parcel_receiver')
                    ->label(__('user.receiver'))
                    ->getStateUsing(function (Parcel $record) {
                        if (is_null($record->receiver_name) == true) {
                            $receiver_name = $record->receiver->name;
                        } else {
                            $receiver_name = $record->receiver_name;
                        }

                        return new HtmlString(__('parcel.to') . ': ' . $receiver_name . '<br/>' . __('user.receiver') . ': ' . $record->pickup_person_name . '<br/>' . __('app.phone_number') . ': ' . $record->pickup_person_contact_no);
                    })
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('app.description'))
                    ->toggleable(),
                TextColumn::make('courier.name')
                    ->label(__('parcel.courier_company'))
                    ->toggleable(),
                TextColumn::make('tracking_no')
                    ->label(__('parcel.tracking_number'))
                    ->copyable()
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('parcel_images')
                    ->collection('parcel_images')
                    ->imageSize(150)
                    ->action(
                        Action::make('viewImages')
                            ->modalHeading('Parcel Images')
                            ->modalSubmitAction(false)
                            ->modalContent(fn($record) => view(
                                'filament.resources.parcel-resource.image-preview',
                                [
                                    'images' => $record->getMedia('parcel_images'),
                                ],
                            ))
                            ->modalWidth('3xl')
                    ),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        if ($state == ParcelStatus::PENDING_PICK_UP->value) {
                            return __('parcel.pending_pickup');
                        } elseif ($state == ParcelStatus::PICKED_UP->value) {
                            return __('parcel.picked_up');
                        } elseif ($state == ParcelStatus::NOT_MY_PARCEL->value) {
                            return __('parcel.not_my_parcel');
                        }
                    })
                    ->colors([
                        'warning' => fn($state): bool => $state === 0,
                        'success' => fn($state): bool => $state === 1,
                        'danger' => fn($state): bool => $state === 2,
                    ])
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Parcel $record) {
                        return $record->created_at->format('d-M-y');
                    })
                    ->toggleable(),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Parcel $record) {
                        return $record->created_at->format('H:i:s');
                    })
                    ->toggleable(),
                TextColumn::make('pickup_time')
                    ->label(__('parcel.pickup_time'))
                    ->dateTime()
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('signature_image')
                    ->label(__('app.signature_image'))
                    ->collection('signature_image'),
                TextColumn::make('updated_at')
                    ->label(__('app.updated_at'))
                    ->getStateUsing(function (Parcel $record) {
                        return date('d-M-y H:i:s', strtotime($record->updated_at));
                    })
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('residence_filters')
                    ->visible(fn() => Filament::auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Mooban Type & Sub Type')
                            ->columns(3)
                            ->schema([
                                Select::make('mooban_type')
                                    ->options(collect(MoobanType::cases())->mapWithKeys(fn($type) => [
                                        $type->value => $type->getLabel(),
                                    ]))
                                    ->preload()
                                    ->multiple(),
                                Select::make('sub_type')
                                    ->label(__('residence.house_type'))
                                    ->options(function (Get $get) {
                                        $moobanTypes = (array) $get('mooban_type');

                                        if (empty($moobanTypes)) {
                                            return [];
                                        }

                                        $filteredSubTypes = collect($moobanTypes)
                                            ->flatMap(fn($moobanType) => MoobanType::tryFrom($moobanType)?->allowedSubTypes() ?? [])
                                            ->unique();

                                        return $filteredSubTypes->mapWithKeys(fn($subType) => [
                                            $subType->value => $subType->getLabel(),
                                        ])->toArray();
                                    })
                                    ->multiple(),
                                Select::make('residence_id')
                                    ->label(__('app.residence_mooban'))
                                    ->options(list_residences())
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['mooban_type']),
                                fn(Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn($q) => $q->whereIn('mooban_type', (array) $data['mooban_type'])
                                )
                            )
                            ->when(
                                ! empty($data['sub_type']),
                                fn(Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn($q) => $q->whereIn('sub_type', (array) $data['sub_type'])
                                )

                            )
                            ->when(
                                $data['residence_id'],
                                fn(Builder $query) => $query->whereHas(
                                    'unit.residence',
                                    fn($q) => $q->where('id', $data['residence_id'])
                                )
                            );
                    }),
                Filter::make('province_filters')
                    ->visible(fn() => Filament::auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Province, District, Subdistrict & Main Road')
                            ->columns(3)
                            ->schema([
                                Select::make('province')
                                    ->label(__('app.province'))
                                    ->options(fn() => ThailandProvince::get()->pluck('name_in_english', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->multiple(),

                                Select::make('district')
                                    ->label(__('app.district'))
                                    ->options(function (Get $get) {
                                        $provinceIds = (array) $get('province');

                                        if (empty($provinceIds)) {
                                            return [];
                                        }

                                        return ThailandDistrict::whereIn('province_id', $provinceIds)
                                            ->get()
                                            ->pluck('name_in_english', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->multiple(),

                                Select::make('subdistrict')
                                    ->label(__('app.subdistrict'))
                                    ->options(function (Get $get) {
                                        $districtIds = (array) $get('district'); // Ensure it's an array

                                        if (empty($districtIds)) {
                                            return [];
                                        }

                                        return ThailandSubDistrict::whereIn('district_id', $districtIds)
                                            ->get()
                                            ->pluck('name_in_english', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->multiple(),

                                Select::make('main_road')
                                    ->label(__('residence.main_road'))
                                    ->options(function (Get $get) {
                                        return ThailandLocationService::getMainRoadsByDistricts(
                                            array_map('intval', array_filter((array) $get('district')))
                                        );
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['province']),
                                fn(Builder $query) => $query->whereHas('unit.residence.subdistrict.district.province', function ($q) use ($data) {
                                    return $q->whereIn('id', (array) $data['province']); // Handle multiple province IDs
                                })
                            )
                            ->when(
                                ! empty($data['district']),
                                fn(Builder $query) => $query->whereHas('unit.residence.subdistrict.district', function ($q) use ($data) {
                                    return $q->whereIn('id', (array) $data['district']); // Handle multiple district IDs
                                })
                            )
                            ->when(
                                ! empty($data['subdistrict']),
                                fn(Builder $query) => $query->whereHas('unit.residence.subdistrict', function ($q) use ($data) {
                                    return $q->whereIn('id', (array) $data['subdistrict']); // Handle multiple subdistrict IDs
                                })
                            )
                            ->when(
                                $data['main_road'] ?? null,
                                fn(Builder $query): Builder => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    return $q->where('main_road', 'LIKE', '%' . $data['main_road'] . '%');
                                })
                            );
                    }),
                Filter::make('date_filters')
                    ->label(__('app.dates_filtering'))
                    ->visible(fn() => Filament::auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                    ->columnSpan(3)
                    ->schema([
                        Fieldset::make('Created & Updated Filter')
                            ->columns(3)
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label(__('app.created_from')),
                                DatePicker::make('created_until')
                                    ->label(__('app.created_until')),
                                DatePicker::make('updated_from')
                                    ->label(__('app.last_updated_from')),
                                DatePicker::make('updated_until')
                                    ->label(__('app.last_updated_until')),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                ! empty($data['created_from']),
                                fn(Builder $q) => $q->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                ! empty($data['created_until']),
                                fn(Builder $q) => $q->whereDate('created_at', '<=', $data['created_until'])
                            )
                            ->when(
                                ! empty($data['updated_from']),
                                fn(Builder $q) => $q->whereDate('updated_at', '>=', $data['updated_from'])
                            )
                            ->when(
                                ! empty($data['updated_until']),
                                fn(Builder $q) => $q->whereDate('updated_at', '<=', $data['updated_until'])
                            );
                    }),
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
                    })
                    ->visible(fn(Component $livewire): bool => $livewire instanceof ListParcels),
                Filter::make('parcel_id')
                    ->label(__('parcel.parcel_id'))
                    ->schema([
                        TextInput::make('parcel_id')
                            ->label(__('parcel.parcel_id')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['parcel_id'])) {
                            return $query->where('parcel_generated_no', $data['parcel_id']);
                        }
                    }),
                Filter::make('tracking_no')
                    ->schema([
                        TextInput::make('tracking_no')
                            ->label(__('parcel.tracking_number')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tracking_no'],
                                fn(Builder $query): Builder => $query->where('tracking_no', $data['tracking_no']),
                            );
                    }),
                Filter::make('to')
                    ->label(__('To'))
                    ->schema([
                        TextInput::make('to')
                            ->label(__('parcel.to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['to'],
                                fn(Builder $query): Builder => $query->where('receiver_name', 'LIKE', '%' . $data['to'] . '%'),
                            );
                    }),
                Filter::make('receiver')
                    ->schema([
                        TextInput::make('receiver_name')
                            ->label(__('parcel.receiver_name')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['receiver_name'],
                                fn(Builder $query): Builder => $query->where('pickup_person_name', 'LIKE', '%' . $data['receiver_name'] . '%'),
                            );
                    }),
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options([
                        ParcelStatus::PENDING_PICK_UP->value => __('parcel.pending_pickup'),
                        ParcelStatus::PICKED_UP->value => __('parcel.picked_up'),
                        ParcelStatus::NOT_MY_PARCEL->value => __('parcel.not_my_parcel'),
                    ]),
                SelectFilter::make('courier_id')
                    ->label(__('parcel.courier_company'))
                    ->options(
                        LogisticPartner::where('category', CategoryType::Courier->value)
                            ->orderBy('id')
                            ->pluck('name', 'id')
                    )
                    ->searchable(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkAction::make('updateStatus')
                    ->label(__('app.update_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->schema([
                        Select::make('status')
                            ->label(__('app.status'))
                            ->options([
                                ParcelStatus::PICKED_UP->value => __('parcel.picked_up'),
                                ParcelStatus::NOT_MY_PARCEL->value => __('parcel.not_my_parcel'),
                            ])
                            ->live()
                            ->required(),

                        Grid::make(2)
                            ->schema([
                                Select::make('pickup_type')
                                    ->label(__('parcel.pickup_type'))
                                    ->options([
                                        PickupType::RECIPIENT->value => PickupType::RECIPIENT->getLabel(),
                                        PickupType::ON_BEHALF->value => PickupType::ON_BEHALF->getLabel(),
                                    ])
                                    ->visible(
                                        fn(callable $get) =>
                                        $get('status') === ParcelStatus::PICKED_UP->value
                                    )
                                    ->required(
                                        fn(callable $get) =>
                                        $get('status') === ParcelStatus::PICKED_UP->value
                                    )
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {

                                        if ($state === PickupType::RECIPIENT->value) {
                                            // fill from first selected record (bulk action)
                                            $record = $livewire->getSelectedTableRecords()?->first();
                                
                                            $set('pickup_person_name', $record?->receiver_name);
                                        }
                                
                                        if ($state === PickupType::ON_BEHALF->value) {
                                            $set('pickup_person_name', null);
                                        }
                                    })
                                    ->live(),

                                Select::make('recipient_resident_id')
                                    ->label(__('parcel.recipient'))
                                    ->options(function ($livewire) {

                                        $record = $livewire->getSelectedTableRecords()?->first();

                                        if (! $record?->unit_id) {
                                            return [];
                                        }

                                        return UnitUser::query()
                                            ->where('unit_id', $record->unit_id)
                                            ->get()
                                            ->pluck('user.name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->live()
                                    ->visible(function ($get, $livewire) {

                                        $record = $livewire->getSelectedTableRecords()?->first();

                                        return $get('status') === ParcelStatus::PICKED_UP->value
                                            && $get('pickup_type') === PickupType::RECIPIENT->value
                                            && $record?->receiver_name === 'All';
                                    })
                                    ->required(function ($get, $livewire) {

                                        $record = $livewire->getSelectedTableRecords()?->first();

                                        return $get('pickup_type') === PickupType::RECIPIENT->value
                                            && $record?->receiver_name === 'All';
                                    })->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            return;
                                        }
                                
                                        $unitUser = UnitUser::with('user')->find($state);
                                
                                        $set('pickup_person_name', $unitUser?->user?->name);
                                    }),
                                TextInput::make('pickup_person_name')
                                    ->label(__('parcel.pickup_person_name'))
                                    ->hidden(function ($get, $livewire) {

                                        $record = $livewire->getSelectedTableRecords()?->first();
                                
                                        return $get('pickup_type') === PickupType::RECIPIENT->value
                                            && $record?->receiver_name === 'All';
                                    })
                                    ->visible(
                                        fn ($get) =>
                                            $get('status') === ParcelStatus::PICKED_UP->value
                                    )
                                    ->disabled(function ($get, $livewire) {
                                        $record = $livewire->getSelectedTableRecords()?->first();
                                    
                                        return ! (
                                            $get('pickup_type') === PickupType::ON_BEHALF->value
                                            || (
                                                $get('pickup_type') === PickupType::RECIPIENT->value
                                                && $record?->receiver_name === 'All'
                                            )
                                        );
                                    })
                                    ->required(function ($get, $livewire) {
                                
                                        $record = $livewire->getSelectedTableRecords()?->first();
                                
                                        return ! (
                                            $get('pickup_type') === PickupType::RECIPIENT->value
                                            && $record?->receiver_name === 'All'
                                        );
                                    })
                                    ->reactive(),

                                TextInput::make('pickup_person_contact_no')
                                    ->label(__('parcel.pickup_person_phone_number'))
                                    ->visible(
                                        fn(callable $get) =>
                                        $get('status') === ParcelStatus::PICKED_UP->value
                                    ),

                                SignaturePad::make('signature_image')
                                    ->label(__('app.signature_image'))
                                    ->exportBackgroundColor('rgba(0,0,0,0)')
                                    ->exportPenColor('#000000')
                                    ->visible(
                                        fn(callable $get) =>
                                        $get('status') === ParcelStatus::PICKED_UP->value
                                    ),
                            ]),
                    ])
                    ->modalWidth('2xl')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        if ($records->contains(
                            fn($record) =>
                            $record->status !== ParcelStatus::PENDING_PICK_UP->value
                        )) {
                            Notification::make()
                                ->title(__('app.invalid_selection'))
                                ->body(__('parcel.only_parcel_with_pending_pickup_status_can_be_updated'))
                                ->danger()
                                ->send();

                            return;
                        }

                        foreach ($records as $record) {
                            $updateData = [
                                'status' => $data['status'],
                                'updated_by_mmb_user_id' => auth()->id(),
                                'pickup_person_contact_no' => $data['pickup_person_contact_no'],
                            ];

                            if ($data['status'] === ParcelStatus::PICKED_UP->value) {
                                $updateData['pickup_time'] = now();
                                $updateData['pickup_type'] = $data['pickup_type'];

                                if ($data['pickup_type'] === PickupType::RECIPIENT->value) {
                                    if (
                                        $record->receiver_name === 'All'
                                        && ! empty($data['recipient_resident_id'])
                                    ) {
                                
                                        $unitUser = UnitUser::with('user')
                                            ->find($data['recipient_resident_id']);
                                
                                        $updateData['pickup_person_name'] = $unitUser?->user?->name;
                                
                                    } else {
                                
                                        $updateData['pickup_person_name'] = $record->receiver_name;
                                    }
                                
                                } else {
                                    $updateData['pickup_person_name'] = $data['pickup_person_name'];
                                }
                            }

                            $record->update($updateData);

                            if (! empty($data['signature_image'])) {

                                $tempFile = tempnam(sys_get_temp_dir(), 'signature');
                        
                                file_put_contents(
                                    $tempFile,
                                    base64_decode(
                                        preg_replace(
                                            '#^data:image/\w+;base64,#i',
                                            '',
                                            $data['signature_image']
                                        )
                                    )
                                );
                        
                                $record
                                    ->addMedia($tempFile)
                                    ->usingFileName('signature_' . now()->timestamp . '.png')
                                    ->toMediaCollection('signature_image', 'cos');
                            }
                        }

                        Notification::make()
                            ->title(__('app.success'))
                            ->body(__('parcel.parcel_status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
                FilamentExportBulkAction::make('export')
                    ->label(__('app.export'))
                    ->fileName('Parcel-Report')
                    ->fileNameFieldLabel(__('app.file_name'))
                    ->formatFieldLabel(__('app.format'))
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        // Visible table columns
                        $columns = collect($livewire->getTable()->getColumns())
                            ->filter(fn($column) => $column->isVisible())
                            ->map(fn($column) => $column->getName())
                            ->values()
                            ->all();

                        // bulk action form data
                        $fileName = ($data['file_name'] ?? 'Parcel-Report') . '.xlsx';

                        // Selected records
                        $parcels = $livewire->getSelectedTableRecords();

                        $parcels = Parcel::whereIn('id', $parcels->pluck('id'))
                            ->latest('id')
                            ->get();

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported parcels records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new ParcelExport($parcels, $columns), $fileName);
                    }),
                DeleteBulkAction::make()
                    ->hidden(auth()->user()->hasRole(['Admin'])),
            ]);
    }
}
