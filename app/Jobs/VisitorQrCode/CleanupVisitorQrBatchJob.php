<?php

namespace App\Jobs\VisitorQrCode;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CleanupVisitorQrBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        public string $batchId,
    ) {
        $this->onQueue('VisitorQrChunk');
    }

    public function handle(): void
    {
        /*
         * Get the selected visitor card IDs.
         */
        $visitorCardIds = Cache::get(
            "visitor-card-qr-batch:{$this->batchId}"
        );

        if (! is_array($visitorCardIds)) {
            return;
        }

        /*
         * Delete every generated QR image.
         */
        foreach ($visitorCardIds as $visitorCardId) {

            $cacheKey = "visitor-card-qr:{$this->batchId}:{$visitorCardId}";

            $path = Cache::get($cacheKey);

            if ($path) {
                Storage::disk('public')->delete($path);
            }

            /*
             * Delete individual QR cache.
             */
            Cache::forget($cacheKey);
        }

        /*
         * Delete batch cache.
         */
        Cache::forget(
            "visitor-card-qr-batch:{$this->batchId}"
        );

        /*
         * Delete progress cache.
         */
        Cache::forget(
            "visitor-card-qr-completed:{$this->batchId}"
        );
    }
}
