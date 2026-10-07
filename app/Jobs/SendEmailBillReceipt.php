<?php

namespace App\Jobs;

use App\Enums\Bill\BillStatus;
use App\Mail\MailBillReceipt;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailBillReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $transactionId;
    protected array $emails;

    public function __construct(int $transactionId, array $emails)
    {
        $this->transactionId = $transactionId;
        $this->emails = $emails;
    }

    public function handle()
    {
        $transaction = Transaction::with([
            'invoice.unit.residence',
            'invoice.billPayeeSetting.residence.propertyManagementUser',
            'invoice.items',
            'invoice.payers.user',
            'paymentMethod',
        ])->findOrFail($this->transactionId);

        $residentNames = $transaction->invoice->payers
            ->map(fn($payer) => $payer->user?->name ? ucfirst($payer->user->name) : null)
            ->filter()
            ->implode(', ');

        $items = $transaction->invoice->items
            ->where('status', '!=', BillStatus::CANCEL->value);

        $grandTotal = $items->sum('price');

        $base64MooBanLogo = $transaction?->invoice?->billPayeeSetting?->residence?->propertyManagementUser?->image_base64 ?? null;

        $data = [
            'transaction' => $transaction,
            'residentName' => $residentNames,
            'items' => $items,
            'grandTotal' => $grandTotal,
            'base64MooBanLogo' => $base64MooBanLogo,
        ];

        $subject = 'Payment Receipt for Bill invoice ' . $transaction->invoice->invoice_no;

        $pdf = PDF::loadView('bills.receipt-full', $data)->setPaper('A4');

        // Only send to users with valid emails
        if (!empty($this->emails)) {
            Mail::to($this->emails)->send(new MailBillReceipt($data, $subject, $pdf->output(), $transaction->ref_no));
        }
    }
}