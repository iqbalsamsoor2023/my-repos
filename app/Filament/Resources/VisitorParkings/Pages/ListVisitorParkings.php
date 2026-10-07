<?php

namespace App\Filament\Resources\VisitorParkings\Pages;

use App\Enums\User\RoleType;
use App\Jobs\ExportVisitorParkings;
use Carbon\Carbon;
use Filament\Actions\Action;
use App\Filament\Resources\VisitorParkings\Widgets\PfmsCollectionsLineChart;
use App\Filament\Resources\VisitorParkings\Widgets\PfmsStatsOverview;
use App\Filament\Resources\VisitorParkings\Widgets\VisitorParkingDownloadWidget;
use App\Filament\Resources\VisitorParkings\VisitorParkingResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListVisitorParkings extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = VisitorParkingResource::class;

    public function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 25;
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export parking records')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->hidden(function () {
                    return auth()->user()->hasRole('Super Admin');
                })
                ->label(__('visitor.parking_export_action'))
                ->schema([
                    DatePicker::make('export_from')
                        ->label(__('visitor.parking_export_from_date'))
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
                        ->helperText(__('visitor.parking_export_helper_from')),
                    DatePicker::make('export_until')
                        ->label(__('visitor.parking_export_until_date'))
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
                        ->helperText(__('visitor.parking_export_helper_range')),
                    Select::make('export_format')
                        ->label(__('visitor.parking_export_format'))
                        ->options([
                            'xlsx' => __('visitor.parking_export_format_xlsx'),
                            'csv' => __('visitor.parking_export_format_csv'),
                        ])
                        ->default('xlsx')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $fromDate = Carbon::parse($data['export_from']);
                    $untilDate = Carbon::parse($data['export_until']);
                    $daysDiff = (int) $fromDate->diffInDays($untilDate);

                    if ($daysDiff > 90) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('visitor.parking_export_invalid_range_title'))
                            ->body(__('visitor.parking_export_invalid_range_body', ['days' => $daysDiff]))
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->exportParkingRecords($data);
                })
                ->modalHeading(__('visitor.parking_export_modal_heading'))
                ->modalDescription(__('visitor.parking_export_modal_description'))
                ->modalWidth('md'),
            Action::make('index')
                ->label(__('visitor.parkings'))
                ->url(route('filament.admin.resources.parkings.index')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PfmsCollectionsLineChart::class,
            PfmsStatsOverview::class,
            VisitorParkingDownloadWidget::class,
        ];
    }

    protected function exportParkingRecords(array $data): void
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

            ExportVisitorParkings::dispatch(
                auth()->id(),
                $fromDate->startOfDay()->toDateTimeString(),
                $untilDate->endOfDay()->toDateTimeString(),
                $format,
                $residenceIds
            )->onQueue('VisitorParkingQueue');

            \Filament\Notifications\Notification::make()
                ->title(__('visitor.parking_export_request_sent_title'))
                ->body(__('visitor.parking_export_request_sent_body', ['days' => $daysDiff]))
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Visitor parking export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'date_range' => $fromDate->toDateString() . ' to ' . $untilDate->toDateString(),
                'days_requested' => $daysDiff,
                'residence_ids' => $residenceIds ?? 'all'
            ]);

            \Filament\Notifications\Notification::make()
                ->title(__('visitor.parking_export_failed_title'))
                ->body(__('visitor.parking_export_failed_body'))
                ->danger()
                ->send();
        }
    }
}

