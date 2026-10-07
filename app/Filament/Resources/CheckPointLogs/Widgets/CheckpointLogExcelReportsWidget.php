<?php

namespace App\Filament\Resources\CheckPointLogs\Widgets;

use App\Enums\AutoSendReport\ModuleType;
use App\Models\Report;
use App\Repositories\ReportRepository;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class CheckpointLogExcelReportsWidget extends BaseWidget
{
    use InteractsWithTable;

    protected static ?string $heading = 'PGS Excel Reports Lists';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole(['Super Admin', 'Admin', 'Property Management']), 403);
    }

    protected function getTableQuery(): Builder
    {
        $maxDateLimit = new Carbon('3 months ago');

        $query = Report::query()
            ->where('module', ModuleType::PGS)
            ->where('format', 'xlsx')
            ->whereDate('report_date', '>=', $maxDateLimit)
            ->latest('report_date');

        if (Auth::user()->hasRole('Property Management')) {
            $residence = Auth::user()->propertyManagement;
            $query = $query->where('residence_id', $residence->id);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('residence.name')
                    ->label(__('app.residence_mooban'))
                    ->searchable()
                    ->hidden(auth()->user()->hasRole(['Property Management'])),
                TextColumn::make('report_date')
                    ->label(__('checkpoint.report_date'))
                    ->formatStateUsing(fn ($state): ?string => localize_date_time($state)),
                TextColumn::make('expire_at')
                    ->label(__('app.expire_at'))
                    ->formatStateUsing(fn ($state): ?string => localize_date_time($state)),
            ])
            ->emptyStateHeading(__('checkpoint.no_reports'))
            ->filtersFormColumns(2)
            ->filters([
                Filter::make('report_date')
                    ->schema([
                        DatePicker::make('reported_from')
                            ->label(__('checkpoint.reported_from'))
                            ->minDate(now()->subMonths(3))
                            ->maxDate(now()),
                        DatePicker::make('reported_until')
                            ->label(__('checkpoint.reported_until'))
                            ->default(now())
                            ->minDate(now()->subMonths(3))
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['reported_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('report_date', '>=', $date),
                            )
                            ->when(
                                $data['reported_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('report_date', '<=', $date),
                            );
                    }),
            ], FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('download')
                    ->label(__('app.download'))
                    ->url(fn (Report $record): string => Storage::disk('cos')->url($record->filepath))
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->toolbarActions([
                BulkAction::make('export')
                    ->label(__('app.export'))
                    ->action(function (Component $livewire) {
                        $data = [
                            'project' => 'MMB2',
                            'user_id' => auth()->user()->id,
                            'ids' => $livewire->getSelectedTableRecords()->pluck('id')->toArray(),
                            'format' => 'xlsx',
                            'module' => ModuleType::PGS,
                        ];

                        $repository = new ReportRepository;
                        $repository->export($data);

                        $livewire->dispatch(CheckpointLogBulkReportDownloadWidget::EXPORT_STARTED_EVENT);

                        Notification::make()
                            ->title(__('checkpoint.export_started'))
                            ->body(__('checkpoint.export_started_description'))
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => Auth::user()->hasRole(['Property Management']))
                    ->requiresConfirmation()
                    ->modalHeading(__('checkpoint.download_selected_reports'))
                    ->modalDescription(__('checkpoint.download_confirm_description'))
                    ->modalSubmitActionLabel(__('checkpoint.proceed_download')),
            ]);
    }
}
