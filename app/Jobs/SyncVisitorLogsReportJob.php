<?php

namespace App\Jobs;

use App\Console\Commands\SyncVisitorLogsToReport;
use App\Notifications\VmsEtlFailedNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SyncVisitorLogsReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300; // 5 minutes

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600; // 1 hour

    /**
     * Job parameters.
     */
    protected ?string $from;
    protected ?string $to;
    protected bool $fullSync;
    protected bool $includeArchive;
    protected int $chunkSize;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ?string $from = null,
        ?string $to = null,
        bool $fullSync = false,
        bool $includeArchive = false,
        int $chunkSize = 500
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->fullSync = $fullSync;
        $this->includeArchive = $includeArchive;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        Log::channel('vms_etl')->info('VMS ETL job started', [
            'from' => $this->from,
            'to' => $this->to,
            'full_sync' => $this->fullSync,
            'include_archive' => $this->includeArchive,
            'chunk_size' => $this->chunkSize,
        ]);

        try {
            $exitCode = Artisan::call('vms:sync-report', [
                '--from' => $this->from,
                '--to' => $this->to,
                '--full' => $this->fullSync,
                '--chunk' => $this->chunkSize,
                '--archive' => $this->includeArchive,
            ]);

            $duration = round(microtime(true) - $startTime, 2);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Log::channel('vms_etl')->info('VMS ETL job completed successfully', [
                    'duration' => $duration,
                    'exit_code' => $exitCode,
                ]);
            } else {
                throw new \Exception("ETL command failed with exit code {$exitCode}. Output: {$output}");
            }
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);

            Log::channel('vms_etl')->error('VMS ETL job failed', [
                'duration' => $duration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send failure notification
            $this->notifyFailure($e);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('vms_etl')->critical('VMS ETL job permanently failed after all retries', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);

        // Send critical failure notification
        $this->notifyFailure($exception, true);
    }

    /**
     * Send failure notification to Slack.
     */
    protected function notifyFailure(\Throwable $exception, bool $isFinal = false): void
    {
        try {
            // Get Slack webhook URL from config
            $slackWebhookUrl = config('services.slack.vms_etl_webhook');

            if (!$slackWebhookUrl) {
                Log::warning('Slack webhook URL not configured for VMS ETL notifications');
                return;
            }

            Notification::route('slack', $slackWebhookUrl)
                ->notify(new VmsEtlFailedNotification(
                    $exception,
                    $isFinal,
                    $this->attempts()
                ));
        } catch (\Exception $notificationException) {
            Log::error('Failed to send VMS ETL failure notification', [
                'error' => $notificationException->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['vms', 'etl', 'reporting', 'visitor-logs'];
    }
}
