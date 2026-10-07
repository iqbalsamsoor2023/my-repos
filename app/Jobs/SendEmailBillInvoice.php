<?php

namespace App\Jobs;

use App\Actions\Invoice\GenerateInvoiceFileDataAction;
use App\Mail\MailBillInvoice;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailBillInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $invoiceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Load invoice with relationships
        $invoice = Invoice::with(['payers.user'])->findOrFail($this->invoiceId);

        // Generate invoice data
        $generateInvoiceFileDataAction = new GenerateInvoiceFileDataAction;
        $data = $generateInvoiceFileDataAction->execute($invoice);

        // Email subject
        $date = Carbon::parse($invoice->bill_date)->format('M Y');
        $subject = 'Your bill for ' . $date;

        // Extract emails safely
        $emails = $invoice->payers
            ->pluck('user.email')
            ->filter()      // remove null
            ->unique()      // remove duplicates
            ->values()
            ->all();

        // Stop if no emails
        if (empty($emails)) {
            return;
        }

        // Generate PDF
        $pdf = Pdf::loadView('bills.bill-invoice-full', $data)
            ->setPaper('A4', 'portrait');

        // Send email
        Mail::to($emails)->send(
            new MailBillInvoice(
                $data,
                $subject,
                $pdf->output(),
                $invoice->invoice_no
            )
        );
    }
}