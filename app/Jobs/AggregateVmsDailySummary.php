<?php

namespace App\Jobs;

use App\Models\VmsAnalyticsDaily;
use App\Services\VmsStatsViewSyncService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateVmsDailySummary implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(
        public ?string $summaryDate = null,
        public bool $force = false
    ) {
    }

    public function uniqueId(): string
    {
        $date = Carbon::parse($this->summaryDate ?? now()->subDay()->toDateString())->toDateString();

        return "aggregate-vms-daily:{$date}:" . ((int) $this->force);
    }

    public function handle(): string
    {
        $date = Carbon::parse($this->summaryDate ?? now()->subDay()->toDateString())->toDateString();

        if (! $this->force && VmsAnalyticsDaily::where('summary_date', $date)->exists()) {
            return 'skipped';
        }

        if ($this->force) {
            VmsAnalyticsDaily::where('summary_date', $date)->delete();
        }

        $from = Carbon::parse($date)->startOfDay();
        $until = Carbon::parse($date)->endOfDay();

        VmsStatsViewSyncService::syncRange(
            from: $from,
            until: $until,
            chunkSize: 500,
            onProgress: null,
            sleepMs: 0
        );

        VmsStatsViewSyncService::syncWidgetAggregates();

        return 'processed';
    }
}
