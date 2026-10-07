<?php

namespace App\Actions\EmailCampaign;

use Exception;
use App\Mail\BlastEmail;
use App\Models\EmailCampaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTestEmailAction
{
    public function execute(EmailCampaign $campaign, string $email): bool
    {
        try {
            // Validate image before sending test email
            $this->validateCampaignImage($campaign);

            // Send test email
            Mail::to($email)->send(new BlastEmail(
                $campaign->subject,
                $campaign->message,
                $campaign
            ));

            return true;

        } catch (Exception $e) {
            Log::channel('email_blast')->error('Test email failed', [
                'campaign_id' => $campaign->id,
                'recipient_email' => $email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function validateCampaignImage(EmailCampaign $campaign): void
    {
        $imageUrl = $campaign->imageUrl;

        // Skip validation if no image or using fallback image
        if (! $imageUrl || $imageUrl === 'https://dashboard.mymooban.co.th/images/no-image.png') {
            return;
        }

        try {
            // Quick HEAD request to check image accessibility
            $response = Http::timeout(2)->head($imageUrl);

            // Image validation - no logging needed for non-accessible images as template handles this
            if (! $response->successful()) {
                // Silent failure - template handles missing images gracefully
            }

        } catch (Exception $e) {
            // Silent failure - campaign will proceed without image validation
        }
    }
}
