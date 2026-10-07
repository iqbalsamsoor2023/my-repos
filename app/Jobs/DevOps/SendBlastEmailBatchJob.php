<?php

namespace App\Jobs\DevOps;

use InvalidArgumentException;
use App\Mail\BlastEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\EmailCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBlastEmailBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600; // 10 minutes - increased for large batches

    public $maxExceptions = 2;

    public $backoff = [30, 120, 300]; // Shorter backoff for MySQL

    public function __construct(
        public array $recipientIds,
        public int $campaignId,
        public string $subject,
        public string $message
    ) {
        // Set the queue name using the method from Queueable trait
        $this->onQueue('EmailBlastQueue');

        if (count($recipientIds) > 100) {
            throw new InvalidArgumentException('Batch size too large for MySQL queue. Maximum 100 recipients per batch.');
        }
    }

    public function handle(EmailCampaignService $campaignService): void
    {
        $campaign = EmailCampaign::select('id', 'subject', 'message')->find($this->campaignId);
        if (! $campaign) {
            return;
        }

        // SendGrid can handle much faster rates - using 100ms delay (10 emails/sec)
        // This is well within SendGrid's limits and won't trigger rate limiting
        $emailDelay = 100000; // 0.1 seconds (100ms)
        $processedCount = 0;

        $recipients = EmailCampaignRecipient::whereIn('id', $this->recipientIds)
            ->where('status', 'pending')
            ->select('id', 'email')
            ->get()
            ->keyBy('id');

        if ($recipients->isEmpty()) {
            return;
        }

        $sentIds = [];
        $failedIds = [];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new BlastEmail($this->subject, $this->message, $campaign)
                );

                $sentIds[] = $recipient->id;
                usleep($emailDelay);
            } catch (Throwable $e) {
                Log::channel('email_blast')->error('Email send failed', [
                    'campaign_id' => $this->campaignId,
                    'recipient_id' => $recipient->id,
                    'email' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);

                $failedIds[] = $recipient->id;
            }

            // Memory cleanup every 25 emails
            $processedCount++;
            if ($processedCount % 25 === 0) {
                gc_collect_cycles();
            }
        }

        // Mass update - sent recipients
        if (! empty($sentIds)) {
            EmailCampaignRecipient::whereIn('id', $sentIds)
                ->update(['status' => 'sent', 'updated_at' => now()]);
        }

        // Mass update - failed recipients
        if (! empty($failedIds)) {
            EmailCampaignRecipient::whereIn('id', $failedIds)
                ->update(['status' => 'failed', 'updated_at' => now()]);
        }

        // Final cleanup
        unset($recipients, $campaign);
        gc_collect_cycles();
    }

    public function failed(Throwable $exception): void
    {
        EmailCampaignRecipient::whereIn('id', $this->recipientIds)
            ->where('status', '!=', 'sent')
            ->update(['status' => 'failed']);
    }

    /**
     * Calculate the number of seconds to wait before retrying.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }
}
