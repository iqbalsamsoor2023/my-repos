<?php

namespace App\Filament\Resources\DailyActivityReports\Tables;

use App\Models\Sgoc\DailyActivityReport;
use App\Models\Sgoc\ShiftType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyActivityReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shiftType.shift_name')
                    ->label(__('company.shift_type'))
                    ->description(fn(DailyActivityReport $record) => $record?->shiftType?->shift_name_th),
                TextColumn::make('reportedBy.email')
                    ->label(__('app.email'))
                    ->description(fn(DailyActivityReport $record) => $record?->reportedBy?->name)
                    ->copyable(),
                TextColumn::make('reported_by_name')
                    ->label(__('user.security_guard_staff'))
                    ->copyable(),
                TextColumn::make('reporting_time')
                    ->label(__('Reporting Time'))
                    ->getStateUsing(function (DailyActivityReport $record) {
                        if (!$record->start_at || !$record->end_at) {
                            return '-';
                        }
            
                        $date = $record->start_at->format('d-M-Y');
                        $startTime = $record->start_at->format('H:i:s');
                        $endTime = $record->end_at->format('H:i:s');
            
                        return "
                            <div class='font-medium'>{$startTime} - {$endTime}</div>
                        ";
                    })
                    ->html(),
                ViewColumn::make('reports')
                    ->label(__('report.reports'))
                    ->view('tables.columns.daily-activity-report.report')
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label(__('app.image'))
                    ->toggleable(),
                TextColumn::make('examined_person_names')
                    ->label(__('report.examined_persons'))
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->wrap(),
                ColumnGroup::make(__('app.created_at'), [
                    TextColumn::make('created_at_date')
                        ->label(__('app.date'))
                        ->getStateUsing(function (DailyActivityReport $record) {
                            return $record->created_at->format('d-M-y');
                        }),
                    TextColumn::make('created_at_time')
                        ->label(__('app.time'))
                        ->getStateUsing(function (DailyActivityReport $record) {
                            return $record->created_at->format('H:i:s');
                        }),
                    ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Filter::make('shift_type')
                    ->schema([
                        Select::make('shift_type_id')
                            ->label(__('company.shift_type'))
                            ->options(
                                ShiftType::whereIn('shift_name', ['Day Shift', 'Night Shift'])
                                    ->pluck(app()->getLocale() === 'th' ? 'shift_name_th' : 'shift_name', 'id')
                            )
                            ->placeholder(__('company.select_shift_type')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['shift_type_id'])) {
                            $query->where('shift_type_id', $data['shift_type_id']);
                        }
                    }),
                Filter::make('security_guard_staff')
                    ->schema([
                        TextInput::make('security_guard_staff')
                            ->label(__('user.security_guard_staff')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['security_guard_staff'])) {
                            return $query->where('reported_by_name', 'LIKE', '%' . $data['security_guard_staff'] . '%');
                        }
                    }),
                Filter::make('created_at')
                    ->schema([
                       DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('report')
                        ->label(__('report.report'))
                        ->icon(Heroicon::OutlinedDocument)
                        ->url(fn(DailyActivityReport $record): string => route('daily-activity-report.report', $record))
                        ->openUrlInNewTab(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
