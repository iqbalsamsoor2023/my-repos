<?php

namespace App\Jobs\DevOps;

use Throwable;
use App\Models\EmailCampaign;
use App\Services\EmailCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // Increase to 10 minutes

    public $memory = 512; // Set memory limit to 512MB

    /**
     * Calculate the number of seconds to wait before retrying.
     */
    public $backoff = [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes

    public function __construct(int $campaignId)
    {
        $this->onQueue('EmailBlastQueue');

        $this->campaignId = $campaignId;
    }

    public function handle(EmailCampaignService $campaignService): void
    {
        try {
            $campaign = EmailCampaign::findOrFail($this->campaignId);

            Log::info('Starting background email campaign processing', [
                'campaign_id' => $this->campaignId,
                'subject' => $campaign->subject,
            ]);

            // Process the campaign in background
            $campaignService->queueBlast($campaign);

            Log::info('Background email campaign processing completed', [
                'campaign_id' => $this->campaignId,
            ]);

        } catch (Throwable $e) {
            Log::error("Background email campaign processing failed", [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update campaign status to failed
            EmailCampaign::where('id', $this->campaignId)->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ProcessEmailCampaignJob failed', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);

        // Update campaign status to failed
        EmailCampaign::where('id', $this->campaignId)->update([
            'status' => 'failed',
        ]);
    }
}
