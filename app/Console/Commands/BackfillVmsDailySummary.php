<?php

namespace App\Console\Commands;

use App\Jobs\AggregateVmsDailySummary;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class BackfillVmsDailySummary extends Command
{
    protected $signature = 'vms:backfill-summary
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--queue : Dispatch to queue}
                            {--force : Force re-process existing dates}';

    protected $description = 'Backfill VMS daily analytics summary for historical dates';

    public function handle(): int
    {
        $from = $this->option('from') ?? now()->subDays(90)->toDateString();
        $to = $this->option('to') ?? now()->subDay()->toDateString();
        $useQueue = (bool) $this->option('queue');
        $force = (bool) $this->option('force');

        try {
            $fromDate = Carbon::parse($from);
            $toDate = Carbon::parse($to);
        } catch (\Throwable) {
            $this->error('Invalid date format. Use Y-m-d.');

            return self::FAILURE;
        }

        if ($fromDate->isAfter($toDate)) {
            $this->error('Start date must be before or equal to end date.');

            return self::FAILURE;
        }

        $period = CarbonPeriod::create($fromDate, $toDate);
        $totalDays = $period->count();

        $this->info("Backfilling VMS summary from {$fromDate->toDateString()} to {$toDate->toDateString()}");
        $this->info("Total days: {$totalDays}");

        if (! $this->confirm('Continue?', true)) {
            $this->info('Backfill cancelled.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalDays);
        $bar->start();

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($period as $date) {
            $dateString = $date->toDateString();

            try {
                if ($useQueue) {
                    AggregateVmsDailySummary::dispatch($dateString, $force)->onQueue('StatsSync');
                    $processed++;
                } else {
                    $job = new AggregateVmsDailySummary($dateString, $force);
                    $result = $job->handle();

                    if ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $processed++;
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed {$dateString}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Count'],
            [
                ['Processed', $processed],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Total', $totalDays],
            ]
        );

        if ($useQueue) {
            $this->info('Jobs dispatched to StatsSync.');
        }

        return self::SUCCESS;
    }
}
