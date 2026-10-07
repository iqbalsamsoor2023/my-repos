<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BlastEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public ?EmailCampaign $campaign = null
    ) {
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.blast')
            ->text('emails.blast-text')
            ->with([
                'messageBody' => $this->messageBody,
                'subjectLine' => $this->subjectLine,
                'imageUrl' => $this->campaign?->imageUrl,
            ])
            ->withSwiftMessage(function ($message) {
                $headers = $message->getHeaders();

                // Bulk email headers (SendGrid recommended)
                $headers->addTextHeader('Precedence', 'bulk');
                $headers->addTextHeader('X-Auto-Response-Suppress', 'All');

                // SendGrid category for tracking and analytics
                $headers->addTextHeader('X-SMTPAPI', json_encode([
                    'category' => ['community-announcement', 'email-campaign'],
                ]));

                // Improve spam score and deliverability
                $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                $headers->addTextHeader('X-Mailer', config('app.name'));

                // Email classification
                $headers->addTextHeader('X-Message-Category', 'community-announcement');

                // Priority (normal for bulk emails)
                $headers->addTextHeader('Importance', 'Normal');
                $headers->addTextHeader('X-Priority', '3');
            });
    }
}
