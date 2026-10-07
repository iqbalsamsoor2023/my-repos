<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailBillInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public $subject;

    public $pdf;

    public $invoice_no;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $subject, $pdf, $invoice_no)
    {
        $this->data = $data;
        $this->subject = $subject;
        $this->pdf = $pdf;
        $this->invoice_no = $invoice_no;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.bill.bill-invoice', $this->data)
            ->from('e-billing@mymooban.co.th', 'MyMooBan E-Bill Reminder')
            ->subject($this->subject)
            ->attachData($this->pdf, $this->invoice_no.'.pdf');
    }
}
