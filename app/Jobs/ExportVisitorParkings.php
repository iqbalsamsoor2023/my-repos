<?php

namespace App\Jobs;

use App\Exports\VisitorParkingExport;
use App\Models\VisitorParking;
use App\Models\VisitorParkingArchive;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportVisitorParkings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1800; // 30 minutes for large exports

    protected $userId;
    protected $dateFrom;
    protected $dateUntil;
    protected $format;
    protected $residenceIds;

    public function __construct(int $userId, string $dateFrom, string $dateUntil, string $format, ?array $residenceIds = null)
    {
        $this->userId = $userId;
        $this->dateFrom = $dateFrom;
        $this->dateUntil = $dateUntil;
        $this->format = $format;
        $this->residenceIds = $residenceIds;
    }

    public function handle(): void
    {
        try {
            $fromDate = Carbon::parse($this->dateFrom);
            $toDate = Carbon::parse($this->dateUntil);

            $records = collect();

            if (Schema::hasTable('visitor_parkings_archive')) {
                $archiveQuery = VisitorParkingArchive::query()
                    ->with([
                        'visitorLog:id,visitor_id,visitor_generated_no,vehicle_type,vehicle_plate_no,arrival_time,leave_time,created_at',
                        'visitorLog.visitor:id,name',
                        'visitorLog.visitingArrangements:id,visitor_log_id,unit_id',
                        'visitorLog.visitingArrangements.unit:id,unit_number',
                        'calculation:id',
                        'calculation.parking:id,type',
                    ])
                    ->whereHas('visitorLog', function ($q) use ($fromDate, $toDate) {
                        $q->whereBetween('created_at', [
                            $fromDate->startOfDay(),
                            $toDate->endOfDay()
                        ]);

                        if ($this->residenceIds) {
                            $q->whereIn('residence_id', $this->residenceIds);
                        }
                    });

                $archiveRecords = $archiveQuery->get();
                $records = $records->merge($archiveRecords);
            }

            $liveQuery = VisitorParking::query()
                ->with([
                    'visitorLog:id,visitor_id,visitor_generated_no,vehicle_type,vehicle_plate_no,arrival_time,leave_time,created_at',
                    'visitorLog.visitor:id,name',
                    'visitorLog.visitingArrangements:id,visitor_log_id,unit_id',
                    'visitorLog.visitingArrangements.unit:id,unit_number',
                    'calculation:id',
                    'calculation.parking:id,type',
                ])
                ->whereHas('visitorLog', function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('created_at', [
                        $fromDate->startOfDay(),
                        $toDate->endOfDay()
                    ]);

                    if ($this->residenceIds) {
                        $q->whereIn('residence_id', $this->residenceIds);
                    }
                });

            $liveRecords = $liveQuery->get();
            $records = $records->merge($liveRecords);

            $records = $records
                ->unique('id')
                ->sortByDesc('created_at')
                ->values();

            if ($records->isEmpty()) {
                $this->notifyUser(
                    __('visitor.parking_export_no_data_title'),
                    __('visitor.parking_export_no_data_body'),
                    'warning'
                );
                return;
            }

            // Define columns for export
            $columns = [
                'visitorLog.visitor_generated_no',
                'visitorLog.visitor.name',
                'visitorLog.vehicle_type',
                'visitorLog.vehicle_plate_no',
                'unit_to_visit',
                'arrival_date',
                'visitorLog.arrival_time',
                'departure_date',
                'visitorLog.leave_time',
                'parking_hour',
                'is_free_parking',
                'is_chartered',
                'is_penalty',
                'is_stamp',
                'calculation.parking.discount_type',
                'amount_paid',
                'change',
                'amount_to_pay',
                'voucher_image',
                'created_at',
                'created_at_time',
                'updated_at',
                'updated_at_time',
            ];

            // Generate filename with timestamp and user ID to ensure uniqueness
            $timestamp = now()->format('YmdHis');
            $fileName = "visitor_parkings_user{$this->userId}_{$fromDate->format('Ymd')}_{$toDate->format('Ymd')}_{$timestamp}.{$this->format}";
            $filePath = "exports/visitor-parkings/{$fileName}";

            // Ensure directory exists
            $directory = 'exports/visitor-parkings';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Export to file
            $export = new VisitorParkingExport($records, $columns);

            if ($this->format === 'xlsx') {
                Excel::store($export, $filePath, 'public', \Maatwebsite\Excel\Excel::XLSX);
            } else {
                Excel::store($export, $filePath, 'public', \Maatwebsite\Excel\Excel::CSV);
            }

            $this->notifyUser(
                __('visitor.parking_export_ready_title'),
                __('visitor.parking_export_ready_body', ['count' => $records->count()]),
                'success'
            );
        } catch (\Exception $e) {
            Log::error('Visitor parking export failed', [
                'user_id' => $this->userId,
                'date_from' => $this->dateFrom,
                'date_until' => $this->dateUntil,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->notifyUser(
                __('visitor.parking_export_failed_title'),
                __('visitor.parking_export_failed_job_body'),
                'danger'
            );

            throw $e;
        }
    }

    protected function notifyUser(string $title, string $body, string $status): void
    {
        $user = \App\Models\User::find($this->userId);

        if ($user) {
            \Filament\Notifications\Notification::make()
                ->title($title)
                ->body($body)
                ->status($status)
                ->sendToDatabase($user);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExportVisitorParkings job failed permanently', [
            'user_id' => $this->userId,
            'date_from' => $this->dateFrom,
            'date_until' => $this->dateUntil,
            'error' => $exception->getMessage(),
        ]);

        $this->notifyUser(
            __('visitor.parking_export_failed_title'),
            __('visitor.parking_export_failed_job_permanent'),
            'danger'
        );
    }
}
