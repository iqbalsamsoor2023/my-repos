<?php

namespace App\Jobs\ImageProcessing;

use App\Models\Parcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessParcelImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $parcelId;

    public $tempPath;

    public $fileType;

    public $fileField;

    public $uniqueFileName;

    public $timeout = 300;

    public $tries = 3;

    public $maxExceptions = 3;

    public $backoff = [30, 60, 120]; // Retry after 30s, 60s, 120s

    public function __construct($parcelId, $tempPath, $fileType, $fileField, $uniqueFileName)
    {
        $this->parcelId = $parcelId;
        $this->tempPath = $tempPath;
        $this->fileType = $fileType;
        $this->fileField = $fileField;
        $this->uniqueFileName = $uniqueFileName;

        $this->onQueue('ParcelImageProcessing');
    }

    public function handle(): void
    {
        $log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/parcel-' . date('Y-m-d') . '.log'),
        ]);
    
        $parcel = Parcel::find($this->parcelId);
    
        if (! $parcel) {
            $log->warning("Parcel not found", [
                'parcelId' => $this->parcelId,
            ]);
    
            $this->safeCleanup();
            return;
        }
    
        $disk = Storage::disk('local');
    
        if (! $disk->exists($this->tempPath)) {
            $log->error("Temp file missing", [
                'parcelId' => $this->parcelId,
                'path' => $this->tempPath,
            ]);
    
            return;
        }
    
        try {
            $media = $this->uploadWithRetry($parcel);
    
            $this->verifyUpload($media);
    
            $this->safeCleanup();
    
            $log->info("Upload SUCCESS", [
                'parcelId' => $this->parcelId,
                'mediaId' => $media->id ?? null,
            ]);
    
        } catch (Throwable $e) {
    
            $log->error("Upload FAILED permanently", [
                'parcelId' => $this->parcelId,
                'error' => $e->getMessage(),
            ]);
    
            throw $e;
        }
    }

    private function uploadWithRetry($parcel)
    {
        $maxAttempts = 3;
        $baseDelay = 2;

        $lastException = null;

        $log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/parcel-' . date('Y-m-d') . '.log'),
        ]);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            try {
                $log->info('Uploading to Tencent', [
                    'parcelId' => $this->parcelId,
                    'attempt' => $attempt,
                ]);

                $path = Storage::disk('local')->path($this->tempPath);

                $media = $parcel
                    ->addMedia($path)
                    ->usingFileName($this->uniqueFileName)
                    ->withCustomProperties([
                        'type' => $this->fileType,
                        'upload_attempt' => $attempt,
                    ])
                    ->toMediaCollection($this->fileField);

                return $media;
            } catch (Throwable $e) {

                $log->warning('Upload attempt failed', [
                    'parcelId' => $this->parcelId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    sleep($baseDelay ** $attempt);
                }

                $lastException = $e;
            }
        }

        throw new \Exception(
            "Tencent upload failed after {$maxAttempts} attempts: " .
                $lastException->getMessage()
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

    private function safeCleanup(): void
    {
        $log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/parcel-' . date('Y-m-d') . '.log'),
        ]);

        try {
            $disk = Storage::disk('local');

            $disk->delete($this->tempPath);

            $dir = dirname($this->tempPath);

            if (count($disk->files($dir)) === 0) {
                $disk->deleteDirectory($dir);
            }

            $log->info("Temp cleaned successfully", [
                'parcelId' => $this->parcelId,
                'dir' => $this->tempPath,
            ]);
        } catch (Throwable $e) {

            $log->error("Cleanup failed", [
                'parcelId' => $this->parcelId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
