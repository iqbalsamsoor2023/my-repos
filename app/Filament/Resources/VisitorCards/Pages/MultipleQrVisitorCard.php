<?php

namespace App\Filament\Resources\VisitorCards\Pages;

use App\Filament\Resources\VisitorCards\VisitorCardResource;
use App\Jobs\VisitorQrCode\GenerateVisitorQrChunkJob;
use App\Models\VisitorCard;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Cache;

class MultipleQrVisitorCard extends Page
{
    protected static string $resource = VisitorCardResource::class;

    protected string $view = 'filament.resources.visitor-card-resource.pages.multiple-qr-visitor-card';

    public string $visitorCardIdsHash;

    public int $total = 0;

    public int $completed = 0;

    public bool $jobsDispatched = false;

    public function mount(string $visitorCardIdsHash): void
    {
        $this->visitorCardIdsHash = $visitorCardIdsHash;

        $ids = Cache::get(
            "visitor-card-qr-batch:{$visitorCardIdsHash}"
        );

        if (! is_array($ids) || empty($ids)) {
            abort(404, 'Visitor card batch not found.');
        }

        $this->total = count($ids);

        /*
         * Dispatch only once.
         */
        if (! Cache::has(
            "visitor-card-qr-dispatched:{$visitorCardIdsHash}"
        )) {
            foreach (array_chunk($ids, 50) as $chunk) {
                GenerateVisitorQrChunkJob::dispatch(
                    visitorCardIds: $chunk,
                    batchId: $visitorCardIdsHash,
                );
            }

            Cache::put(
                "visitor-card-qr-dispatched:{$visitorCardIdsHash}",
                true,
                now()->addHours(6)
            );
        }

        $this->completed = (int) Cache::get(
            "visitor-card-qr-completed:{$visitorCardIdsHash}",
            0
        );
    }

    public function refreshProgress(): void
    {
        $this->completed = (int) Cache::get(
            "visitor-card-qr-completed:{$this->visitorCardIdsHash}",
            0
        );
    }

    public function getProgress(): int
    {
        if ($this->total <= 0) {
            return 0;
        }

        return min(
            100,
            (int) (($this->completed / $this->total) * 100)
        );
    }

    public function getRecords(): array
    {
        /*
         * Don't generate QR here.
         *
         * Just retrieve already-generated file paths.
         */
        $ids = Cache::get(
            "visitor-card-qr-batch:{$this->visitorCardIdsHash}",
            []
        );

        if (empty($ids)) {
            return [];
        }

        $cards = VisitorCard::query()
            ->with('residence')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $records = [];

        foreach ($ids as $id) {

            $card = $cards->get($id);

            if (! $card) {
                continue;
            }

            $path = Cache::get(
                "visitor-card-qr:{$this->visitorCardIdsHash}:{$id}"
            );

            /*
             * QR isn't generated yet.
             */
            if (! $path) {
                continue;
            }

            $records[] = [
                'visitor_card' => $card,

                'qr_image' => asset(
                    'storage/' . $path
                ),
            ];
        }

        return $records;
    }
}
