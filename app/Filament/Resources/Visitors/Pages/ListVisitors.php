<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Enums\User\RoleType;
use App\Filament\Resources\Visitors\VisitorResource;
use App\Filament\Resources\Visitors\Widgets\VisitorFoodDeliveryChart;
use App\Filament\Resources\Visitors\Widgets\VisitorFoodDeliveryStatsOverview;
use App\Filament\Resources\Visitors\Widgets\VisitorParcelCourierChart;
use App\Filament\Resources\Visitors\Widgets\VisitorParcelCourierStatsOverview;
use App\Filament\Resources\Visitors\Widgets\VisitorPurposeChart;
use App\Filament\Resources\Visitors\Widgets\VisitorPurposeStatsOverview;
use App\Filament\Resources\Visitors\Widgets\VisitorReportDownloadWidget;
use App\Filament\Resources\Visitors\Widgets\VisitorsSummaryChart;
use App\Filament\Resources\Visitors\Widgets\VisitorStatsOverview;
use App\Filament\Resources\Visitors\Widgets\VisitorTypeChart;
use App\Filament\Resources\Visitors\Widgets\VisitorTypeStatsOverview;
use App\Filament\Resources\Visitors\Widgets\VisitorVehicleTypeChart;
use App\Filament\Resources\Visitors\Widgets\VisitorVehicleTypeStatsOverview;
use App\Jobs\SendVisitorExportRequest;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ListVisitors extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = VisitorResource::class;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static ?string $recordTitleAttribute = 'visitor_generated_no';

    public function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 10;
    }

    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    {
        $perPage = $this->getTableRecordsPerPage();

        $baseQuery = $query->toBase();
        $cacheKey = 'visitor_logs_count_'.md5($baseQuery->toSql().json_encode($baseQuery->getBindings()));

        $total = Cache::remember($cacheKey, 300, function () use ($baseQuery) {
            return $baseQuery->getCountForPagination();
        });

        /** @var LengthAwarePaginator $records */
        $records = $query->paginate(
            perPage: ($perPage === 'all') ? $total : $perPage,
            pageName: $this->getTablePaginationPageName(),
            total: $total,
        );

        return $records->onEachSide(0);
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'xl' => 4,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VisitorReportDownloadWidget::class,
            VisitorsSummaryChart::class,
            VisitorStatsOverview::class,
            VisitorTypeChart::class,
            VisitorTypeStatsOverview::class,
            VisitorPurposeChart::class,
            VisitorPurposeStatsOverview::class,
            VisitorVehicleTypeChart::class,
            VisitorVehicleTypeStatsOverview::class,

            VisitorParcelCourierChart::class,
            VisitorParcelCourierStatsOverview::class,
            VisitorFoodDeliveryChart::class,
            VisitorFoodDeliveryStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export visitors')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->hidden(function () {
                    return auth()->user()->hasAnyRole([
                        RoleType::SUPER_ADMIN->value,
                        RoleType::ADMIN->value,
                    ]);
                })
                ->schema([
                    DatePicker::make('export_from')
                        ->label(__('visitor.export_from_date'))
                        ->required()
                        ->default(now()->subDays(30))
                        ->maxDate(now())
                        ->rules(['required', 'date'])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $exportUntil = $get('export_until');
                            if ($state && $exportUntil) {
                                $fromDate = Carbon::parse($state);
                                $untilDate = Carbon::parse($exportUntil);

                                if ($untilDate->diffInDays($fromDate) > 90) {
                                    $set('export_until', $fromDate->addDays(90)->toDateString());
                                }
                            }
                        })
                        ->helperText(__('visitor.export_helper_from')),
                    DatePicker::make('export_until')
                        ->label(__('visitor.export_until_date'))
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

                                if ($untilDate->diffInDays($fromDate) > 90) {
                                    $set('export_until', $fromDate->addDays(90)->toDateString());
                                }
                            }
                        })
                        ->helperText(__('visitor.export_helper_range')),
                    Select::make('export_format')
                        ->label(__('visitor.export_format'))
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                        ])
                        ->default('xlsx')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $fromDate = Carbon::parse($data['export_from']);
                    $untilDate = Carbon::parse($data['export_until']);
                    $daysDiff = (int) $fromDate->diffInDays($untilDate);

                    if ($daysDiff > 90) {
                        Notification::make()
                            ->title(__('visitor.export_invalid_range_title'))
                            ->body(__('visitor.export_invalid_range_body', ['days' => $daysDiff]))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->exportVisitors($data);
                })
                ->modalHeading(__('visitor.export_visitor_data'))
                ->modalDescription(__('visitor.export_modal_description'))
                ->modalWidth('md'),
            Action::make('view historical')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.visitors.historical'))
                ->label(__('visitor.view_historical_visitors')),
            Action::make('download daily reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('filament.admin.resources.visitors.daily-reports'))
                ->hidden(function () {
                    return ! auth()->user()->hasRole([
                        RoleType::SUPER_ADMIN->value,
                        RoleType::ADMIN->value,
                        RoleType::PROPERTY_MANAGEMENT->value,
                        RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
                        RoleType::DEVELOPER->value,
                    ]);
                }),
            Action::make('index')
                ->label(__('menu.visitor_managements'))
                ->url(route('filament.admin.resources.vms-managements.index'))
                ->hidden(function () {
                    return ! auth()->user()->hasRole([
                        RoleType::SUPER_ADMIN->value,
                        RoleType::ADMIN->value,
                        RoleType::PROPERTY_MANAGEMENT->value,
                        RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
                        RoleType::DEVELOPER->value,
                    ]);
                }),
        ];
    }

    protected function exportVisitors(array $data): void
    {
        $fromDate = Carbon::parse($data['export_from']);
        $untilDate = Carbon::parse($data['export_until']);
        $format = $data['export_format'];
        $user = auth()->user();
        $daysDiff = (int) $fromDate->diffInDays($untilDate);

        try {
            $residenceIds = null;

            if ($user->hasRole('Property Management')) {
                $residenceIds = [get_residence_by_property_management($user->id)->id];
            } elseif ($user->hasRole('Property Management Operation Center')) {
                $residenceIds = get_residence_by_property_management_operation_center($user->id);
            } elseif ($user->hasRole('Developer')) {
                $residenceIds = get_residence_by_developer($user->id);
            }

            $exportRequest = [
                'language' => app()->getLocale(),
                'data_type' => 'live',
                'project' => 'MMB2',
                'user_id' => auth()->id(),
                'format' => $format,
                'date_from' => $fromDate->startOfDay()->toDateTimeString(),
                'date_until' => $untilDate->endOfDay()->toDateTimeString(),
                'residence_ids' => $residenceIds,
            ];

            SendVisitorExportRequest::dispatch($exportRequest)->onQueue('VisitorDataExport');

            Notification::make()
                ->title(__('visitor.export_request_sent_title'))
                ->body(__('visitor.export_request_sent_body', ['days' => $daysDiff]))
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Visitor export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'date_range' => $fromDate->toDateString().' to '.$untilDate->toDateString(),
                'days_requested' => $daysDiff,
                'residence_ids' => $residenceIds ?? 'all',
            ]);

            Notification::make()
                ->title(__('visitor.export_failed_title'))
                ->body(__('visitor.export_failed_body'))
                ->danger()
                ->send();
        }
    }
}
