<?php

namespace App\Filament\Resources\IncidentReports\Tables;

use App\Actions\Audit\CreateAuditAction;
use App\Enums\User\RoleType;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class IncidentReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.mooban_or_residence'))
                    ->toggleable()
                    ->description(fn (Model $record): string => $record?->residence?->name_th ?? '-'),
                TextColumn::make('createdBy.name')
                    ->label(__('user.security_guard_staff'))
                    ->toggleable(),
                TextColumn::make('unit.unit_number')
                    ->label(__('unit.unit_number'))
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('app.title'))
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('app.description'))
                    ->limit(30)
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label(__('app.image'))
                    ->collection('default')
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
                                fn (Builder $query): Builder => $query->whereHas('unit.residence', function ($q) use ($data) {
                                    return $q->whereId($data['value']);
                                }),
                            );
                    })
                    ->visible(auth()->user()->hasRole([
                        RoleType::SUPER_ADMIN->value,
                        RoleType::ADMIN->value,
                        RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
                    ])),
                Filter::make('security_guard_staff')
                    ->schema([
                        TextInput::make('security_guard_staff')
                            ->label(__('user.security_guard_staff')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['security_guard_staff'])) {
                            return $query->whereHas('createdBy', function ($q) use ($data) {
                                return $q->where('name', 'LIKE', '%'.$data['security_guard_staff'].'%');
                            });
                        }
                    }),
                Filter::make('unit_number')
                    ->schema([
                        TextInput::make('unit_number')
                            ->label(__('unit.unit_number')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['unit_number'] ?? null,
                            fn ($q, $value) => $q->whereHas('unit', fn ($sub) => $sub->where('unit_number', $value)
                            )
                        );
                    }),
                Filter::make('title')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('app.title')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['title'])) {
                            return $query->where('title', 'LIKE', '%'.$data['title'].'%');
                        }
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label(__('app.created_from'))->default(null),
                        DatePicker::make('created_until')->label(__('app.created_until'))->default(null),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->schema([
                        DatePicker::make('export_from')
                            ->label(__('incident-report.export_from_date'))
                            ->required()
                            ->default(now()->subDays(7))
                            ->maxDate(now())
                            ->rules(['required', 'date'])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $exportUntil = $get('export_until');
                                if ($state && $exportUntil) {
                                    $fromDate = Carbon::parse($state);
                                    $untilDate = Carbon::parse($exportUntil);

                                    if ($untilDate->diffInDays($fromDate) > 7) {
                                        $set('export_until', $fromDate->addDays(7)->toDateString());
                                    }
                                }
                            })
                            ->helperText(__('incident-report.export_helper_from')),
                        DatePicker::make('export_until')
                            ->label(__('incident-report.export_until_date'))
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->rules(['required', 'date', 'after_or_equal:export_from'])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $exportFrom = $get('export_from');
                                if ($state && $exportFrom) {
                                    $fromDate = Carbon::parse($exportFrom);
                                    $untilDate = Carbon::parse($state);

                                    if ($untilDate->diffInDays($fromDate) > 7) {
                                        $set('export_until', $fromDate->addDays(7)->toDateString());
                                    }
                                }
                            })
                            ->helperText(__('incident-report.export_helper_range')),
                        Select::make('export_format')
                            ->label(__('incident-report.export_format'))
                            ->options([
                                'xlsx' => 'Excel (.xlsx)',
                                'pdf' => 'PDF',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data) {
                        $fromDate = Carbon::parse($data['export_from']);
                        $untilDate = Carbon::parse($data['export_until']);
                        $data['module'] = 'IRS';
                        $daysDiff = $untilDate->diffInDays($fromDate);

                        if ($daysDiff > 7) {
                            Notification::make()
                                ->title(__('incident-report.invalid_date_range'))
                                ->body("The date range spans {$daysDiff} days. Maximum allowed is 7 days. Please select a shorter range.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $reportService = new ReportService;
                        $reportService->exportData($data);

                        $audit = new CreateAuditAction;
                        $audit->execute(new Request([
                            'user_type' => get_class(auth()->user()),
                            'user_id' => auth()->id(),
                            'event' => 'exported',
                            'old_values' => [],
                            'new_values' => ['action' => 'exported incident report records'],
                            'url' => request()->fullUrl(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]));
                    })
                    ->modalHeading(__('incident-report.export_incident_report_data'))
                    ->modalDescription(__('incident-report.export_modal_description'))
                    ->modalWidth('md'),
                Action::make('download_daily_reports')
                    ->label(__('incident-report.download_daily_reports'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('filament.admin.resources.incident-reports.daily-reports'))
                    ->hidden(function () {
                        return ! auth()->user()->hasRole([RoleType::SUPER_ADMIN->value, RoleType::PROPERTY_MANAGEMENT->value]);
                    }),
            ]);
    }
}
