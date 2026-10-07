<?php

namespace App\Services;

use Filament\Notifications\Notification;
use Exception;
use App\Actions\Report\ReportExportAction;
use App\Actions\Report\ReportExportByDateAction;
use App\Enums\User\RoleType;
use App\Models\Residence;
use Carbon\Carbon;

class ReportService
{
    public function export(array $request)
    {
        $reportExportAction = new ReportExportAction;

        return $reportExportAction->execute($request);
    }

    public function exportByDate(array $request)
    {
        $reportExportByDateAction = new ReportExportByDateAction;

        return $reportExportByDateAction->execute($request);
    }

    public function exportData(array $data): void
    {
        $fromDate = Carbon::parse($data['export_from']);
        $untilDate = Carbon::parse($data['export_until']);
        $format = $data['export_format'];
        $module = $data['module'];
        $user = auth()->user();

        $daysDiff = $fromDate->diffInDays($untilDate);

        if ($daysDiff > 7) {
            Notification::make()
                ->title('Invalid Date Range')
                ->body("Date range is {$daysDiff} days. Maximum allowed is 7 days.")
                ->danger()
                ->send();

            return;
        }

        try {
            $residenceIds = null;

            if ($user->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
                $residence = Residence::where('property_management_user_id', $user->id)->first();
                $residenceIds = [$residence->id];
            } elseif ($user->hasRole(RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value)) {
                $residenceIds = get_residence_by_property_management_operation_center($user->id);
            }

            $reportService = app(ReportService::class);

            $exportRequest = [
                'module' => $module,
                'project' => 'MMB',
                'user_id' => auth()->id(),
                'format' => $format,
                'date_from' => $fromDate->startOfDay()->toDateString(),
                'date_until' => $untilDate->endOfDay()->toDateString(),
                'language' => app()->getLocale(),
                'residence_ids' => $residenceIds, // null for Super Admin, specific IDs for PM and PMOC
            ];

            $reportService->exportByDate($exportRequest);

            Notification::make()
                ->title('Export Request Sent')
                ->body("Your export request for {$daysDiff} days of data has been sent to the processing service.")
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('There was an error sending your export request. Please try again.')
                ->danger()
                ->send();
        }
    }
}
