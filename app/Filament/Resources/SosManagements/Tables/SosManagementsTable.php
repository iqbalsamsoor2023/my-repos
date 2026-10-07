<?php

namespace App\Filament\Resources\SosManagements\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\SosManagement\Status;
use App\Enums\SosManagement\UserActionRequest;
use App\Exports\SosManagementExport;
use App\Forms\Components\GoogleMapContainer;
use App\Models\Erp\ThailandProvince;
use App\Models\SosManagement;
use App\Services\FilamentExport\FilamentExportBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class SosManagementsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                TextColumn::make('unit.residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn(SosManagement $record): string => $record?->unit?->residence?->name_th ?? '-')
                    ->hidden(auth()->user()->hasRole(['Property Management'])),
                ViewColumn::make('unit_number')
                    ->label(__('unit.unit'))
                    ->view('tables.columns.sos.unit-details')
                    ->toggleable(),
                ViewColumn::make('resident')
                    ->label(__('app.resident'))
                    ->view('tables.columns.sos.resident-details')
                    ->toggleable(),
                TextColumn::make('user_action_request')
                    ->label(__('app.type_of_alert'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->colors([
                        'warning' => fn($state): bool => $state === __('app.' . strtolower(Status::PENDING->name)),
                        'primary' => fn($state): bool => $state === __('app.' . strtolower(Status::IN_PROGRESS->name)),
                        'danger' => fn($state): bool => $state === __('app.' . strtolower(Status::CANCELLED->name)),
                        'success' => fn($state): bool => $state === __('app.' . strtolower(Status::COMPLETED->name)),
                    ])
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label(__('app.remark'))
                    ->toggleable(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (SosManagement $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (SosManagement $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('province')
                    ->label(__('app.province'))
                    ->options(ThailandProvince::get()->pluck('name_in_english', 'id'))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('unit.residence.subdistrict.district.province', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible($user->hasAnyRole(['Super Admin', 'Admin'])),
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
                    ->visible($user->hasAnyRole(['Super Admin', 'Admin'])),
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
                        TextInput::make('resident_name')
                            ->label(__('user.resident_name')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['resident_name'])) {
                            return $query->whereHas('createdBy', function ($q) use ($data) {
                                return $q->where('name', 'LIKE', '%' . $data['resident_name'] . '%');
                            });
                        }
                    }),
                SelectFilter::make('user_action_request')
                    ->label(__('app.type_of_alert'))
                    ->options([
                        UserActionRequest::CALL_AMBULANCE->value => str_replace('_', ' ', ucfirst(strtolower(UserActionRequest::CALL_AMBULANCE->name))),
                        UserActionRequest::CALL_POLICE->value => str_replace('_', ' ', ucfirst(strtolower(UserActionRequest::CALL_POLICE->name))),
                    ]),
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options([
                        Status::PENDING->value => __('app.' . strtolower(Status::PENDING->name)),
                        Status::IN_PROGRESS->value => str_replace('_', ' ', ucfirst(strtolower(Status::IN_PROGRESS->name))),
                        Status::CANCELLED->value => __('app.' . strtolower(Status::CANCELLED->name)),
                        Status::COMPLETED->value => __('app.' . strtolower(Status::COMPLETED->name)),
                    ]),
                SelectFilter::make('remark')
                    ->label(__('app.remark'))
                    ->options([
                        'Sorry, I pressed wrongly.' => (__('Sorry, I pressed wrongly.')),
                        "Thank You. I'm safe now" => (__("Thank You. I'm safe now")),
                    ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['created_from']) && isset($data['created_until'])) {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                Action::make('location')
                    ->icon('heroicon-c-map-pin')
                    ->label('View location')
                    ->schema([
                        GoogleMapContainer::make('Location')
                            ->hiddenLabel(),
                    ])
                    ->modalSubmitAction(fn(Action $action) => $action->hidden())
                    ->modalCancelAction(fn(Action $action) => $action->label('Close'))
                    ->visible(fn(SosManagement $record): bool => $record->latitude !== '0.0' && $record->longitude !== '0.0' && ! is_null($record->latitude) && ! is_null($record->longitude)),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                FilamentExportBulkAction::make('export')
                    ->fileName('SOS-Report')
                    ->disableAdditionalColumns()
                    ->disableCsv()
                    ->disablePdf()
                    ->disableFilterColumns()
                    ->action(function (Component $livewire) {
                        $columns = collect($livewire->getTable()->getColumns())
                                        ->filter(fn ($col) => $col->isVisible())
                                        ->map(fn ($col) => $col->getName())
                                        ->values()
                                        ->toArray(); 
                        $fileName = $livewire->mountedActions[0]['data']['file_name'] . '.xlsx';

                        $ids = $livewire->getSelectedTableRecords()->pluck('id')->toArray();
                        $sosMangements = SosManagement::whereIn('id', $ids)->latest('id')->get();

                        $audit = new CreateAuditAction();
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported SOS records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));

                        return Excel::download(new SosManagementExport($sosMangements, $columns), $fileName);
                    }),
            ]);
    }
}
