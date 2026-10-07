<?php

namespace App\Filament\Resources\PrebookVisitors\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\Visitor\ArrivalType;
use App\Enums\Visitor\VehicleType;
use App\Exports\PrebookVisitorExport;
use App\Models\PreregisterVisitor;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PrebookVisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn (PreregisterVisitor $record): string => $record?->unit?->residence?->name_th ?? '-'),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->toggleable(),
                TextColumn::make('visitor.name')
                    ->label(__('app.name'))
                    ->toggleable(),
                TextColumn::make('visitor_purpose')
                    ->label(__('visitor.purpose_of_visit'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('arrival_type')
                    ->label(__('visitor.arrival_type'))
                    ->formatStateUsing(function (?string $state): string {
                        $type = ArrivalType::tryFrom((int) $state);

                        return $type?->getLabel() ?? '-';
                    })
                    ->toggleable(),
                TextColumn::make('vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->formatStateUsing(function ($state) {
                        $vehicleTypeMappings = [
                            VehicleType::CAR->value => __('vehicle.'.strtolower(VehicleType::CAR->name)),
                            VehicleType::TRUCK->value => __('vehicle.'.strtolower(VehicleType::TRUCK->name)),
                            VehicleType::MOTORBIKE->value => __('vehicle.'.strtolower(VehicleType::MOTORBIKE->name)),
                            VehicleType::VAN->value => __('vehicle.'.strtolower(VehicleType::VAN->name)),
                            VehicleType::TAXI->value => __('vehicle.'.strtolower(VehicleType::TAXI->name)),
                            VehicleType::PICKUP->value => __('vehicle.'.strtolower(VehicleType::PICKUP->name)),
                        ];

                        $vehicleType = $vehicleTypeMappings[$state] ?? '-';

                        return $vehicleType;
                    })
                    ->toggleable(),
                TextColumn::make('vehicle_plate_no')
                    ->label(__('vehicle.vehicle_plate_number'))
                    ->toggleable(),
                TextColumn::make('validity_start_date')
                    ->label(__('visitor.validity_start_date'))
                    ->toggleable()
                    ->getStateUsing(function (PreregisterVisitor $record) {
                        return date('d-M-y H:i', strtotime($record->validity_start_date));
                    }),
                TextColumn::make('validity_end_date')
                    ->label(__('visitor.validity_end_date'))
                    ->toggleable()
                    ->getStateUsing(function (PreregisterVisitor $record) {
                        return $record->validity_end_date
                            ? date('d-M-y H:i', strtotime($record->validity_end_date))
                            : '-';
                    }),
                IconColumn::make('is_multiple_entry')
                    ->label(__('visitor.is_multiple_entry'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_qr_code_expired')
                    ->label(__('visitor.is_qr_code_expired'))
                    ->boolean()
                    ->toggleable(),
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
                                fn (Builder $query): Builder => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(auth()->user()->hasRole(['Super Admin', 'Admin', 'Property Management Operation Center'])),
                Filter::make('unit_number')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['unit_number'])) {
                            return $query->whereHas('unit', function ($q) use ($data) {
                                return $q->where('unit_number', 'LIKE', '%'.$data['unit_number'].'%');
                            });
                        }
                    }),
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.name')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['name'])) {
                            return $query->whereHas('visitor', function ($q) use ($data) {
                                return $q->where('name', 'LIKE', '%'.$data['name'].'%');
                            });
                        }
                    }),
                SelectFilter::make('visitor_purpose')
                    ->label(__('visitor.purpose_of_visit'))
                    ->translateLabel()
                    ->options([
                        'Receive/Delivery' => __('visitor.receive_or_delivery'),
                        'Drop Off/Pick Up' => __('visitor.dropoff_or_pickup'),
                        'Contractor/Worker' => __('visitor.contractor_or_worker'),
                        'Visitor Parking' => __('visitor.visitor_parking'),
                        'VIP' => __('visitor.vip'),
                        'Other' => __('app.other'),
                    ]),
                SelectFilter::make('arrival_type')
                    ->label(__('visitor.arrival_type'))
                    ->options([
                        ArrivalType::DRIVE_IN->value => __('visitor.'.strtolower(ArrivalType::DRIVE_IN->name)),
                        ArrivalType::WALK_IN->value => __('visitor.'.strtolower(ArrivalType::WALK_IN->name)),
                    ]),
                SelectFilter::make('vehicle_type')
                    ->label(__('vehicle.vehicle_type'))
                    ->options([
                        VehicleType::CAR->value => __('vehicle.'.strtolower(VehicleType::CAR->name)),
                        VehicleType::TRUCK->value => __('vehicle.'.strtolower(VehicleType::TRUCK->name)),
                        VehicleType::MOTORBIKE->value => __('vehicle.'.strtolower(VehicleType::MOTORBIKE->name)),
                        VehicleType::VAN->value => __('vehicle.'.strtolower(VehicleType::VAN->name)),
                        VehicleType::TAXI->value => __('vehicle.'.strtolower(VehicleType::TAXI->name)),
                        VehicleType::PICKUP->value => __('vehicle.'.strtolower(VehicleType::PICKUP->name)),
                    ]),
                TernaryFilter::make('is_multiple_entry')->label(__('visitor.is_multiple_entry')),
                TernaryFilter::make('is_qr_code_expired')->label(__('visitor.is_qr_code_expired')),
                Filter::make('validity_start_date')
                    ->label(__('visitor.validity_start_date'))
                    ->schema([
                        DatePicker::make('validity_start_from')
                            ->label(__('visitor.validity_start_from')),
                        DatePicker::make('validity_start_until')
                            ->label(__('visitor.validity_start_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['validity_start_from']) && isset($data['validity_start_until'])) {
                            return $query
                                ->when(
                                    $data['validity_start_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('validity_start_date', '>=', $date),
                                )
                                ->when(
                                    $data['validity_start_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('validity_start_date', '<=', $date),
                                );
                        }
                    }),
                Filter::make('validity_end_date')
                    ->label(__('visitor.validity_end_date'))
                    ->schema([
                        DatePicker::make('validity_end_from')
                            ->label(__('visitor.validity_end_from')),
                        DatePicker::make('validity_end_until')
                            ->label(__('visitor.validity_end_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['validity_end_from']) && isset($data['validity_end_until'])) {
                            return $query
                                ->when(
                                    $data['validity_end_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('validity_end_date', '>=', $date),
                                )
                                ->when(
                                    $data['validity_end_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('validity_end_date', '<=', $date),
                                );
                        }
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('qr')
                        ->label(__('app.qr_code'))
                        ->icon('heroicon-o-qr-code')
                        ->url(fn (PreregisterVisitor $record): string => route('filament.admin.resources.prebook-helpdesks.qr', $record))
                        ->openUrlInNewTab(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('Prebook-Visitor-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        // Visible table columns
                        $columns = collect($livewire->getTable()->getColumns())
                            ->filter(fn ($column) => $column->isVisible())
                            ->map(fn ($column) => $column->getName())
                            ->values()
                            ->all();

                        // bulk action form data
                        $fileName = ($data['file_name'] ?? 'Prebook-Visitor').'.xlsx';

                        // Selected records
                        $prebookVisitors = $livewire->getSelectedTableRecords();

                        $prebookVisitors = PreregisterVisitor::with([
                            'unit.residence',
                            'visitor',
                        ])
                            ->whereIn('id', $prebookVisitors->pluck('id'))
                            ->latest('id')
                            ->get();

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported prebook visitor records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new PrebookVisitorExport($prebookVisitors, $columns), $fileName);
                    }),
                DeleteBulkAction::make()
                    ->hidden(auth()->user()->hasRole(['Admin'])),
                BulkAction::make('print')
                    ->label(__('app.print_qr'))
                    ->icon('heroicon-o-printer')
                    ->action(function ($records) {
                        // Extract IDs from the collection
                        $ids = $records->pluck('id')->toArray();

                        // Redirect to print route with selected IDs
                        return redirect()->route('preregister.print.qr', ['ids' => implode(',', $ids)]);
                    }),
            ]);
    }
}
