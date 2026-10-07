<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailBillReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public $subject;

    public $pdf;

    public $receipt_no;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $subject, $pdf, $receipt_no)
    {
        $this->data = $data;
        $this->subject = $subject;
        $this->pdf = $pdf;
        $this->receipt_no = $receipt_no;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.bill.receipt', $this->data)
            ->from('e-receipt@mymooban.co.th', 'MyMooBan E-Bill Reminder Receipt')
            ->subject($this->subject)
            ->attachData($this->pdf, $this->receipt_no.'.pdf');
    }
}
