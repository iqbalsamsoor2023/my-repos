<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PmInvoice\GetInvoiceByIdAction;
use App\Actions\PmInvoice\GetInvoiceListAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Resources\PmInvoice\PmInvoiceCollection;
use App\Http\Resources\PmInvoice\PmInvoiceResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PmInvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $getInvoiceAction = new GetInvoiceListAction();
            $invoice = $getInvoiceAction->execute($request);

            return success(new PmInvoiceCollection($invoice));
        } catch (\Exception $e) {
            return response()->json(['http_code' => $e->getCode() ?: 500,'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function show(int $id)
    {
        try {
            $getOneInvoiceAction = new GetInvoiceByIdAction();
            $invoice = $getOneInvoiceAction->execute($id);

            return success(new PmInvoiceResource($invoice));
        } catch (\Exception $e) {
            return response()->json(['http_code' => $e->getCode() ?: 500,'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function download(int $invoice_id, Request $request)
    {
        try {
            $locale =  $request->header('Accept-Language', 'en');
            $printVersion = $request->input('print_version', 'original');

            // Get invoice
            $getOneInvoiceAction = new GetInvoiceByIdAction();
            $invoice = $getOneInvoiceAction->execute($invoice_id);

            // Get transformed data using InvoicePrintController method
            $data = InvoicePrintController::getInvoiceTransformedData($invoice, $locale);
            $data['invoice'] = $invoice;
            $data['print_version'] = $printVersion;
            $data['locale'] = $locale;

            // Generate PDF
            $pdf = Pdf::loadView('filament.resources.pm-invoices.invoice-pdf', $data);

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

            $fileName = 'invoice_'.$invoice->invoice_number.'.pdf';

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            return response()->json(['http_code' => $e->getCode() ?: 500,'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
