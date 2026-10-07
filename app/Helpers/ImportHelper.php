<?php

namespace App\Helpers;

use Filament\Notifications\Notification as FilamentsNotification;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportHelper
{
    public static function isEmptyRow($row)
    {
        return empty(array_filter($row));
    }

    public static function validateRow($requiredFields): array|string|null
    {
        foreach ($requiredFields as $field) {
            if (empty($field)) {
                return __('validation.required', ['attribute' => __($field)]);
            }
        }

        return null;
    }

    public static function parseExcelDate($date)
    {
        if (empty($date)) {
            return null;
        }

        return is_numeric($date)
            ? Carbon::instance(Date::excelToDateTimeObject($date))->format('Y-m-d')
            : Carbon::parse($date)->format('Y-m-d');
    }

    public static function sendWarningNotification($title, $row = null): FilamentsNotification
    {
        $notification = FilamentsNotification::make()
            ->title($title)
            ->warning()
            ->persistent();

        if ($row !== null) {
            $notification->body('Error in row '.($row + 3));
        }

        return $notification->send();
    }

    public static function sendErrorNotification($title, $body): FilamentsNotification
    {
        $notification = FilamentsNotification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->persistent();

        return $notification->send();
    }

    public static function sendSuccessNotification(): FilamentsNotification
    {
        $notification = FilamentsNotification::make()
            ->title(__('app.import_success'))
            ->success()
            ->send();

        return $notification->send();
    }
}
