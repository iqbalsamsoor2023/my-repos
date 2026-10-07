<?php

namespace App\Jobs\VisitorQrCode;

use App\Actions\Visitor\GenerateVisitorQRChunkAction;
use App\Models\VisitorCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateVisitorQrChunkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        public array $visitorCardIds,
        public string $batchId,
    ) {
        $this->onQueue('VisitorQrChunk');
    }

    public function handle(GenerateVisitorQRChunkAction $generateQr): void
    {
        /*
         * Load all records in this chunk with ONE query.
         */
        $visitorCards = VisitorCard::query()
            ->whereIn('id', $this->visitorCardIds)
            ->get()
            ->keyBy('id');

        foreach ($this->visitorCardIds as $visitorCardId) {

            $visitorCard = $visitorCards->get($visitorCardId);

            if (! $visitorCard) {
                continue;
            }

            $path = $generateQr->execute(
                (string) $visitorCard->visitor_card_no
            );

            /*
             * Store the generated QR path.
             */
            Cache::put(
                "visitor-card-qr:{$this->batchId}:{$visitorCardId}",
                $path,
                now()->addHours(6)
            );

            /*
             * Increment completed count.
             */
            Cache::increment(
                "visitor-card-qr-completed:{$this->batchId}"
            );
        }
    }
}
