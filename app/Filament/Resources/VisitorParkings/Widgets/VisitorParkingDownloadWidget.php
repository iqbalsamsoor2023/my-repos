<?php

namespace App\Filament\Resources\VisitorParkings\Widgets;

use App\Enums\User\RoleType;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VisitorParkingDownloadWidget extends Widget
{
    protected static ?string $heading = null;

    protected string $view = 'filament.resources.visitor-parkings.widgets.visitor-parking-download-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '5s';

    protected function getHeading(): string
    {
        return __('visitor.parking_export_downloads');
    }

    public function mount()
    {
        abort_unless(Auth::user()->hasRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::PROPERTY_MANAGEMENT->value,
            RoleType::PROPERTY_MANAGEMENT_OPERATION_CENTER->value,
            RoleType::DEVELOPER->value]), 403);
    }

    public function getData()
    {
        $directory = 'exports/visitor-parkings';
        $currentUserId = Auth::id();

        // Ensure directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
            return [
                'message' => 'Success',
                'data' => []
            ];
        }

        $files = Storage::disk('public')->files($directory);

        // Filter files to show only current user's exports
        $userFiles = collect($files)->filter(function ($filePath) use ($currentUserId) {
            $fileName = basename($filePath);
            // Extract user ID from filename pattern: visitor_parkings_user{userId}_...
            if (preg_match('/visitor_parkings_user(\d+)_/', $fileName, $matches)) {
                return (int)$matches[1] === $currentUserId;
            }
            return false;
        });

        $fileData = $userFiles->map(function ($filePath) {
            $fileName = basename($filePath);
            $lastModified = Storage::disk('public')->lastModified($filePath);
            $size = Storage::disk('public')->size($filePath);

            // Files expire after 7 days
            $expiresAt = now()->createFromTimestamp($lastModified)->addDays(7);
            $isExpired = now()->greaterThan($expiresAt);

            return [
                'job_id' => pathinfo($fileName, PATHINFO_FILENAME),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'status' => $isExpired ? 'expired' : 'completed',
                'created_at' => $lastModified,
                'expire_at' => $expiresAt->toDateTimeString(),
                'finished_at' => $lastModified,
                'download_link' => Storage::disk('public')->url($filePath),
                'size' => $this->formatBytes($size),
                'progress_now' => 1,
                'progress_max' => 1,
            ];
        })->sortByDesc('created_at')->values();

        return [
            'message' => 'Success',
            'data' => $fileData->toArray()
        ];
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
