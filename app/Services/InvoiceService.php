<?php

namespace App\Services;

use App\Actions\Invoice\GenerateInvoiceFileDataAction;
use App\Actions\Invoice\GetInvoiceAction;
use App\Actions\Invoice\GetOneInvoiceAction;
use App\Http\Requests\Invoice\GetTotalUnpaidInvoiceRequest;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InvoiceService
{
    public function index(Request $request)
    {
        $getInvoiceAction = new GetInvoiceAction;
        $invoice = $getInvoiceAction->execute($request);

        return $invoice;
    }

    public function show(int $id): Model
    {
        $getOneInvoiceAction = new GetOneInvoiceAction;
        $invoice = $getOneInvoiceAction->execute($id);

        return $invoice;
    }

    public function downloadInvoice(int $id)
    {
        $generateInvoiceFileDataAction = new GenerateInvoiceFileDataAction;

        $invoice = Invoice::findOrFail($id);
        $data = $generateInvoiceFileDataAction->execute($invoice);

        $pdf = PDF::loadView('bills.bill-invoice-full', $data)->setPaper('A4', 'portrait');

        return $pdf->download($invoice->invoice_no.'.pdf');
    }

    public function totalUnpaidInvoice(GetTotalUnpaidInvoiceRequest $request)
    {
        $getInvoiceAction = new GetInvoiceAction;
        $request->merge([
            'status' => 1,
        ]);
        $invoices = $getInvoiceAction->execute($request);

        return $invoices->count();
    }
}
