<?php

namespace App\Http\Controllers;

use App\Models\PmInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use NumberToWords\NumberToWords;

class InvoicePrintController extends Controller
{
    public function show(PmInvoice $invoice, Request $request)
    {
        // Fetch query parameters
        $options = $request->only(['version', 'print_version', 'print_option']);

        $data = $this->getInvoiceTransformedData($invoice, $options['version']);

        $data['invoice'] = $invoice;
        $data['print_version'] = $options['print_version'] ?? null;
        $data['locale'] = $options['version'];

        // Generate PDF and return the download
        return $this->pdf_Create('filament.resources.pm-invoices.invoice-pdf', $data, $options['print_option']);
    }

    public static function generatePdf($view, $data = [])
    {
        $pdf = Pdf::loadView($view, $data['record']);

        // Configure Thai font
        $pdf->getDomPDF()->getOptions()->set('defaultFont', 'THSarabunNew');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->getDomPDF()->getOptions()->set('isHtml5ParserEnabled', true);
        $pdf->getDomPDF()->getOptions()->set('isFontSubsettingEnabled', true);
        $pdf->getFontMetrics()->registerFont([
            'family' => 'THSarabunNew',
            'style' => 'normal',
            'weight' => 'normal',
        ], storage_path('fonts/THSarabunNew.ttf'));
        $pdf->setPaper('a4');

        return $pdf;
    }

    public function pdf_Create($view, $data, $options)
    {
        $name = 'invoice_'.$data['invoice']->invoice_number;

        App::setLocale($data['locale']);

        $pdf = self::generatePdf($view, [
            'content' => 'Invoice',
            'record' => $data,
            'locale' => $data['locale'],
        ]);

        if ($options == 'pdf') {
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $name.'.pdf');
        } else {
            return $pdf->stream($name.'.pdf');
        }
    }

    public static function amountToText($amount)
    {
        $numberToWords = new NumberToWords;
        $numberTransformer = $numberToWords->getNumberTransformer('en'); // 'en' for English

        // Separate the integer and decimal parts
        $integerPart = floor($amount);
        $decimalPart = round(($amount - $integerPart) * 100); // For satang (decimal portion)

        // Convert both parts to words
        $integerInWords = $numberTransformer->toWords($integerPart);
        $decimalInWords = $decimalPart > 0 ? $numberTransformer->toWords($decimalPart) : '';

        // Combine the integer and decimal parts
        if ($decimalPart > 0) {
            return ucfirst($integerInWords).' baht and '.$decimalInWords.' satang';
        }

        return ucfirst($integerInWords).' baht';
    }

    public static function amountToText_TH($amount)
    {
        // Make sure we have a valid number
        $amount = floatval($amount);

        // Separate the integer and decimal parts
        $integerPart = floor($amount);
        $decimalPart = round(($amount - $integerPart) * 100); // For satang (decimal portion)

        // Convert both parts to Thai words
        $integerInWords = self::numberToThaiWords($integerPart);
        $decimalInWords = $decimalPart > 0 ? self::numberToThaiWords($decimalPart) : '';

        // Combine the integer and decimal parts
        if ($decimalPart > 0) {
            return $integerInWords.'บาท'.$decimalInWords.'สตางค์';
        }

        return $integerInWords.'บาทถ้วน';
    }

    /**
     * Convert a number to Thai words
     *
     * @param  int  $number  The number to convert
     * @return string The Thai words for the number
     */
    public static function numberToThaiWords($number)
    {
        $number = intval($number);

        if ($number == 0) {
            return 'ศูนย์';
        }

        $digits = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $positions = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

        // Handle special cases for Thai language
        $result = '';
        $len = strlen((string) $number);

        for ($i = 0; $i < $len; $i++) {
            $digit = substr((string) $number, $i, 1);
            $position = $len - $i - 1;

            // Skip if current digit is 0
            if ($digit == 0) {
                continue;
            }

            // Special case for digit 1 at the tens position
            if ($digit == 1 && $position == 1) {
                $result .= 'สิบ';

                continue;
            }

            // Special case for digit 2 at the tens position
            if ($digit == 2 && $position == 1) {
                $result .= 'ยี่สิบ';

                continue;
            }

            // Special case for digit 1 at the ones position
            if ($digit == 1 && $position == 0 && $len > 1) {
                $result .= 'เอ็ด';

                continue;
            }

            // Regular case
            $result .= $digits[$digit];

            // Skip position name for the ones position
            if ($position > 0) {
                // Handle millions
                if ($position % 6 == 0 && $position > 0) {
                    $millionCount = floor($position / 6);
                    $result .= str_repeat('ล้าน', $millionCount);
                } else {
                    $result .= $positions[$position % 6];
                }
            }
        }

        return $result;
    }

    public static function getInvoiceTransformedData($invoice, $version): array
    {
        $billingItems = [];
        $outstandingItems = [];
        $totalBillingAmount = 0;
        $totalOutstandingAmount = 0;
        if (! empty($invoice->billings)) {
            foreach ($invoice->billings as $key => $billing) {
                $row = [];
                $row['billing_number'] = $billing->billing_number;
                $row['billing_date'] = Carbon::parse($billing->billing_date)->format('d/m/y');
                $row['service_duration'] = Carbon::parse($billing->service_duration_from)->format('d/m/y').' - '.Carbon::parse($billing->service_duration_until)->format('d/m/y');
                $row['description'] = $billing->invoiceType->code.'-'.$billing->invoiceType->name;
                $row['unit'] = Number::format($billing->unit, 0);
                $row['total'] = Number::format($billing->grand_total, 2);
                $billingItems[] = $row;
                $totalBillingAmount += $billing->grand_total;
            }
        }

        if (! empty($invoice->outstandingInvoices)) {
            foreach ($invoice->outstandingInvoices as $key => $outstandingInvoice) {
                $row = [];
                $row['invoice_number'] = $outstandingInvoice->invoice_number;
                $row['invoice_date'] = Carbon::parse($outstandingInvoice->invoice_date);
                $row['due_date'] = Carbon::parse($outstandingInvoice->due_date);
                $row['grand_total'] = Number::format($outstandingInvoice->grand_total, 2);
                $outstandingItems[] = $row;
                $totalOutstandingAmount += $outstandingInvoice->grand_total;
            }
        }

        return [
            'subject' => $invoice->subject ?? '',
            'invoice_number' => $invoice->invoice_number ?? '',
            'invoice_date' => (new Carbon(time: $invoice->invoice_date))->format('d M Y'),
            'due_date' => (new Carbon(time: $invoice->due_date))->format('d M Y'),
            'issued_by' => $invoice->issuedUser?->name ?? '',
            'residence_name' => $version == 'en' ? $invoice->residence?->name ?? '' : $invoice->residence?->name_th ?? '',
            'unit_name' => $invoice->unit?->unit_number ?? '',
            'issue_to_name' => $invoice->issue_to_name,
            'items' => $billingItems ?? [],
            'outstandings' => $outstandingItems ?? [],
            'total_billing' => $totalBillingAmount,
            'total_outstanding' => $totalOutstandingAmount,
            'total_amount' => Number::format($invoice->total_amount ?? 0, 2),
            'total_penalty_amount' => Number::format($invoice->total_amount ?? 0, 2),
            'total_vat_amount' => Number::format($invoice->total_amount ?? 0, 2),
            'total_discount_amount' => Number::format($invoice->total_amount ?? 0, 2),
            'grand_total' => Number::format($invoice->total_amount ?? 0, 2),
            'status' => $invoice->status->getLabel() ?? '',
            'remarks' => $invoice->remarks,
            'terms_and_conditions' => $invoice->terms_and_conditions,
            'amount_text' => $version == 'en' ? self::amountToText($invoice->grand_total ?? 0) : self::amountToText_TH($invoice->grand_total ?? 0),
        ];
    }
}
