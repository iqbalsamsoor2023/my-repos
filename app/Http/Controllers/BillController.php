<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\GenerateInvoiceFileDataAction;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class BillController extends Controller
{
    public function invoice(Invoice $invoice)
    {
        $generateInvoiceFileDataAction = new GenerateInvoiceFileDataAction;
        $data = $generateInvoiceFileDataAction->execute($invoice);

        $pdf = PDF::loadView('bills.bill-invoice-full', $data)->setPaper('a4');

        return $pdf->stream();
    }
}
