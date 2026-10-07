<?php

namespace App\Jobs;

use RuntimeException;
use App\Actions\Watermark\ApplyWatermarkAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ApplyWatermarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $mediaId;

    public function __construct(int $mediaId)
    {
        $this->onQueue('WatermarkQueue');
        $this->mediaId = $mediaId;
    }

    public function handle(): void
    {
        try {
            $media = Media::query()->find($this->mediaId);

            if (! $media) {
                Log::warning("Watermark skipped: Media ID {$this->mediaId} not found.");

                return;
            }

            $path = $media->getPath();
            if (! Storage::disk($media->disk)->exists($path)) {
                Log::warning("Watermark skipped: File missing for media ID {$this->mediaId}");
                // throw to retry later
                throw new RuntimeException("File not yet available");
            }

            // 🪄 Execute your watermark logic
            (new ApplyWatermarkAction)->execute($media);

        } catch (Throwable $e) {
            Log::error("Watermark failed for media ID {$this->mediaId}: ".$e->getMessage());
            throw $e; // rethrow so queue retries
        }
    }
}
