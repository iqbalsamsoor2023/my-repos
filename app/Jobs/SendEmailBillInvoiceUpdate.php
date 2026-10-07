<?php

namespace App\Jobs;

use App\Actions\Invoice\GenerateInvoiceFileDataAction;
use App\Enums\Bill\BillStatus;
use App\Mail\MailBillInvoice;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailBillInvoiceUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $invoiceId;

    protected array $emails;

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId, array $emails)
    {
        $this->invoiceId = $invoiceId;
        $this->emails = $emails;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Load invoice with relationships (avoid N+1)
        $invoice = Invoice::with(['payers.user'])->findOrFail($this->invoiceId);

        // Generate data
        $generateInvoiceFileDataAction = new GenerateInvoiceFileDataAction;
        $data = $generateInvoiceFileDataAction->execute($invoice);

        // Subject
        $subject = $invoice->status == BillStatus::CANCEL->value
            ? 'Your bill reminder ' . $invoice->invoice_no . ' has been cancelled!'
            : 'Your bill reminder ' . $invoice->invoice_no . ' has been updated!';

        // Clean emails (no duplicates, no nulls)
        $emails = collect($this->emails)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Stop early if no valid emails
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