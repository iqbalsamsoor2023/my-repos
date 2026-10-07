<?php

namespace App\Mail\AutoSendReport;

use App\Models\AutoSendReport;
use App\Models\Residence;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentASRMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $residence;

    protected $autoSendReport;

    protected $reportTime;

    protected $attachment = null;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Residence $residence, AutoSendReport $autoSendReport, Carbon $reportTime, $attachment = null)
    {
        $this->residence = $residence;
        $this->autoSendReport = $autoSendReport;
        $this->reportTime = $reportTime;
        $this->attachment = $attachment;
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope()
    {
        $dateHuman = $this->reportTime->format('d M Y H:i');
        $residenceName = $this->residence->name;

        return new Envelope(
            subject: "Daily IRS Report for $residenceName $dateHuman",
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content()
    {
        return new Content(
            markdown: 'emails.auto_send_report.incident_asr_mail',
            with: [
                'residence' => $this->residence,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [
            Attachment::fromPath($this->attachment),
        ];
    }
}
