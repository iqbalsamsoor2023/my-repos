<?php

namespace App\Jobs\ImageProcessing;

use App\Actions\Watermark\ApplyWatermarkAction;
use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessVisitorImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $visitorLogId;

    public $tempPath;

    public $fileType;

    public $fileField;

    public $uniqueFileName;

    public $timeout = 300;

    public $tries = 3;

    public function __construct($visitorLogId, $tempPath, $fileType, $fileField, $uniqueFileName)
    {
        $this->visitorLogId = $visitorLogId;
        $this->tempPath = $tempPath;
        $this->fileType = $fileType;
        $this->fileField = $fileField;
        $this->uniqueFileName = $uniqueFileName;

        $this->onQueue('VisitorQueue');
    }

    public function handle(): void
    {
        $log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/visitor-' . date('Y-m-d') . '.log'),
        ]);

        try {

            $visitorLog = VisitorLog::find($this->visitorLogId);

            if (! $visitorLog) {
                $log->warning('VisitorLog not found', [
                    'visitorLogId' => $this->visitorLogId,
                ]);
                return;
            }

            if (! Storage::disk('local')->exists($this->tempPath)) {
                $log->error('Temp file not found', [
                    'visitorLogId' => $this->visitorLogId,
                    'path' => $this->tempPath,
                ]);
                return;
            }

            $localPath = Storage::disk('local')->path($this->tempPath);

            /*
            |--------------------------------------------------------------------------
            | Validate temp file BEFORE upload
            |--------------------------------------------------------------------------
            */
            $log->info('Pre-upload file validation', [
                'visitorLogId' => $this->visitorLogId,
                'tempPath' => $this->tempPath,
                'localPath' => $localPath,
                'exists' => file_exists($localPath),
                'readable' => is_readable($localPath),
                'size' => file_exists($localPath) ? filesize($localPath) : null,
                'mime' => file_exists($localPath)
                    ? mime_content_type($localPath)
                    : null,
            ]);

            if (! file_exists($localPath)) {
                throw new \Exception("Temp file missing: {$localPath}");
            }

            if (filesize($localPath) === 0) {
                throw new \Exception("Temp file is empty");
            }

            /*
            |--------------------------------------------------------------------------
            | Upload Original Image
            |--------------------------------------------------------------------------
            */
            $log->info('Uploading original image to Tencent', [
                'visitorLogId' => $this->visitorLogId,
                'fileField' => $this->fileField,
                'fileType' => $this->fileType,
            ]);

            $media = $this->uploadWithRetry($visitorLog, $log);

            $this->verifyUpload($media);

            $log->info('Original image upload SUCCESS', [
                'visitorLogId' => $this->visitorLogId,
                'mediaId' => $media->id,
                'disk' => $media->disk,
                'url' => $media->getFullUrl(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Check temp file STILL exists after upload
            |--------------------------------------------------------------------------
            */
            $log->info('Post-upload temp file validation', [
                'visitorLogId' => $this->visitorLogId,
                'exists' => Storage::disk('local')->exists($this->tempPath),
                'size' => Storage::disk('local')->exists($this->tempPath)
                    ? filesize($localPath)
                    : null,
                'mime' => Storage::disk('local')->exists($this->tempPath)
                    ? mime_content_type($localPath)
                    : null,
            ]);

            if (! Storage::disk('local')->exists($this->tempPath)) {

                throw new \Exception(
                    "Temp file disappeared after MediaLibrary upload: {$this->tempPath}"
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create & Upload Watermark
            |--------------------------------------------------------------------------
            */
            $log->info('Starting watermark process', [
                'visitorLogId' => $this->visitorLogId,
                'mediaId' => $media->id,
                'tempPath' => $this->tempPath,
            ]);

            (new ApplyWatermarkAction())->execute(
                $visitorLog,
                $media,
                $this->tempPath
            );

            $log->info('Watermark process completed', [
                'visitorLogId' => $this->visitorLogId,
                'mediaId' => $media->id,
            ]);
        } catch (Throwable $exception) {

            $log->error('Upload FAILED permanently', [
                'visitorLogId' => $this->visitorLogId,
                'fileField' => $this->fileField,
                'tempPath' => $this->tempPath,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    private function uploadWithRetry($visitorLog, $log)
    {
        $maxAttempts = 3;
        $baseDelay = 2;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            try {
                $log->info("Upload attempt", [
                    'visitorLogId' => $this->visitorLogId,
                    'fileField' => $this->fileField,
                    'tempPath' => $this->tempPath,
                    'attempt' => $attempt,
                ]);

                $path = Storage::disk('local')->path($this->tempPath);

                if (! file_exists($path)) {
                    throw new \Exception("Upload source file missing: {$path}");
                }

                if (filesize($path) === 0) {
                    throw new \Exception("Upload source file is empty");
                }

                $media = $visitorLog
                    ->addMedia($path)
                    ->usingFileName($this->uniqueFileName)
                    ->withCustomProperties([
                        'type' => $this->fileType,
                        'upload_attempt' => $attempt,
                    ])
                    ->preservingOriginal()
                    ->toMediaCollection($this->fileField);

                return $media;
            } catch (Throwable $e) {

                $lastException = $e;

                $log->warning("Upload attempt failed", [
                    'visitorLogId' => $this->visitorLogId,
                    'fileField' => $this->fileField,
                    'tempPath' => $this->tempPath,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    sleep($baseDelay ** $attempt);
                }
            }
        }

        throw new \Exception(
            "Tencent upload failed after {$maxAttempts} attempts: " .
                ($lastException?->getMessage() ?? 'Unknown error')
        );
    }

    private function verifyUpload($media): void
    {
        if (! $media) {
            throw new \Exception("Missing media after upload");
        }

        $url = $media->getFullUrl();

        if (! $url) {
            throw new \Exception("Missing remote URL from Tencent");
        }

        // HTTP verification (real-world cloud safety check)
        $headers = @get_headers($url, 1);

        if (! $headers || ! str_contains($headers[0], '200')) {
            throw new \Exception("Uploaded file not accessible on Tencent");
        }
    }
}
