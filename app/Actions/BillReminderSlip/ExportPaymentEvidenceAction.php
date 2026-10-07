<?php

namespace App\Actions\BillReminderSlip;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\Response;

class ExportPaymentEvidenceAction
{
    /**
     * Generate the Payment Evidence PDF for the given transactions.
     *
     * @param  Collection<int, Transaction>  $transactions
     */

    public function execute(Collection $transactions): Response
    {

        $isThai = app()->getLocale() === 'th';

        $records = $transactions->map(function (Transaction $transaction) use ($isThai) {
            $paidAt = $transaction->transaction_datetime
                ? Carbon::parse($transaction->transaction_datetime)
                : null;

            return [
                'house_number' => $transaction->invoice?->unit?->unit_number ?? '-',
                'rights_holder' => $transaction->invoice?->billPayeeSetting?->payee_name_th
                    ?: ($transaction->invoice?->billPayeeSetting?->payee_name ?? '-'),
                'prepared_by' => $transaction->payer_name ?? '-',
                'amount' => number_format((float) $transaction->paid_amount, 2),
                'payment_datetime' => $this->formatDate($paidAt, $isThai, withTime: true),
                'payment_date' => $this->formatDate($paidAt, $isThai),
                'payment_time' => $this->formatTime($paidAt, $isThai),
                'additional_details' => $transaction->remark ?: '-',
                'invoice_number' => $transaction->invoice?->invoice_no ?? '-',
                'expense_types' => $transaction->invoice?->items ?? [],
                'slip_image' => $this->getSlipImage($transaction),
            ];
        })->all();

        $pdf = Pdf::loadView('bills.payment-evidence', [
            'records' => $records,
        ])->setPaper('a4', 'portrait');

        $fileName = 'payment-evidence-'.now()->format('Ymd-His').'.pdf';

        // Render now (dompdf reads the local image files during output), then clean up.
        $output = $pdf->output();

        // Return a streamed download so Livewire/Filament handles it as a file
        // download instead of trying to JSON-encode the binary response body.
        return response()->streamDownload(function () use ($output) {
            echo $output;
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Format a date (optionally with time). For Thai locale, use Thai month
     * names and the Buddhist Era year (CE + 543).
     */
    private function formatDate(?Carbon $date, bool $isThai, bool $withTime = false): string
    {
        if (is_null($date)) {
            return '-';
        }

        if ($isThai) {
            $formatted = $date->locale('th')->translatedFormat('j F').' '.($date->year + 543);

            return $withTime ? $formatted.' '.$this->formatTime($date, true) : $formatted;
        }

        $formatted = $date->format('F j, Y');

        return $withTime ? $formatted.' '.$this->formatTime($date, false) : $formatted;
    }

    /**
     * Format a time. Thai uses 24-hour clock with the "น." suffix.
     */
    private function formatTime(?Carbon $date, bool $isThai): string
    {
        if (is_null($date)) {
            return '-';
        }

        return $isThai ? $date->format('H:i').' น.' : $date->format('h:i A');
    }

    /**
     * Resolve the uploaded payment slip photo as a local file path for dompdf.
     *
     * Images are passed as file paths (not base64) because dompdf's HTML5 parser
     * corrupts large base64 data URIs, which prevents the image from rendering.
     */
    private function getSlipImage(Transaction $transaction): ?string
    {
        $media = $transaction->media->first();

        if (is_null($media)) {
            return null;
        }

        try {
            return $media->getUrl();
            
        } catch (\Throwable $e) {
            Log::error("Failed to retrieve bill slip image for transaction {$transaction->id}: {$e->getMessage()}");

            return null;
        }
    }
}
