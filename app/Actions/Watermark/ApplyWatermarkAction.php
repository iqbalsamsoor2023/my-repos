<?php

namespace App\Actions\Watermark;

use App\Models\VisitorLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ApplyWatermarkAction
{
    /**
     * @param  VisitorLog  $visitorLog
     * @param  Media  $media
     * @param  string  $tempPath Path to the original uploaded temp file
     */
    public function execute(VisitorLog $visitorLog, Media $media, string $tempPath)
    {
        // Log::info("Running ApplyWatermarkAction for VisitorLog ID {$visitorLog->id}, Media ID {$media->id}");

        try {
            $localImagePath = storage_path("app/{$tempPath}");

            if (! file_exists($localImagePath)) {
                Log::error("Temp image not found for watermarking: {$localImagePath}");

                return;
            }

            $watermark = public_path('images/mymooban-watermark.png');

            // Apply watermark using Intervention Image (v3)
            $manager = ImageManager::gd();
            $image = $manager->read($localImagePath);
            $image->place($watermark, 'center');

            $fileInfo = pathinfo($media->file_name);
            $watermarkedName = "{$fileInfo['filename']}-watermark.{$fileInfo['extension']}";

            // Determine COS upload path
            $bucket = Storage::disk('cos');

            switch ($media->collection_name) {
                case 'id_image':
                case 'visitor_image':
                    $uploadPath = config('app.path.cos')."/visitor/{$media->model_id}/{$watermarkedName}";
                    break;
                case 'vehicle_image':
                    $uploadPath = config('app.path.cos')."/visitor/vehicle/{$media->model_id}/{$watermarkedName}";
                    break;
                case 'esign_image':
                    $uploadPath = config('app.path.cos')."/visitor/{$media->model_id}/pdpa-sign/{$watermarkedName}";
                    break;
                default:
                    Log::warning("Unknown collection name for Visitor Log ID {$media->model_id}");

                    return;
            }

            // Upload watermarked image to COS
            $bucket->put($uploadPath, (string) $image->encodeByExtension($fileInfo['extension']));

            // Mark media as watermarked
            $media->generated_conversions = ['watermark' => true];
            $media->save();

            // Delete temp file after successful upload
            $this->cleanupTempFile($tempPath);
        } catch (Throwable $exception) {
            Log::error("Failed to apply watermark for VisitorLog ID {$visitorLog->id}: {$exception->getMessage()}", [
                'exception' => $exception,
                'visitorLogId' => $visitorLog->id,
                'mediaId' => $media->id ?? null,
            ]);
        }
    }

    /**
     * Clean up temporary file
     */
    private function cleanupTempFile(string $tempPath): void
    {
        try {
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }

            // Remove empty directory (e.g., temp/visitor/id_image/{visitor_log_id})
            $dirPath = dirname($tempPath);

            $filesInDir = Storage::allFiles($dirPath);

            if (empty($filesInDir)) {
                Storage::deleteDirectory($dirPath);
            }
        } catch (Throwable $exception) {
            Log::error("Failed to cleanup temp file {$tempPath}: ".$exception->getMessage());
        }
    }
}
