<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class BillTransactionController extends Controller
{
    public function show(int $transactionId)
    {
        $transaction = Transaction::with([
            'invoice.unit.residence',
            'invoice.billPayeeSetting.residence.propertyManagementUser',
            'invoice.items',
            'invoice.payers.user',
            'paymentMethod',
        ])->findOrFail($transactionId);

        $residentNames = $transaction->invoice?->payers?->filter(fn($payer) => $payer->user)
            ->map(fn($payer) => ucfirst($payer->user->name))
            ->implode(', ') ?? '';

        $items = $transaction->invoice->items->where('status', '!=', 6);

        $grandTotal = $items->sum('price');

        $base64MooBanLogo = $transaction?->invoice?->billPayeeSetting?->residence?->propertyManagementUser?->image_base64 ?? null;

        $data = [
            'transaction' => $transaction,
            'residentName' => $residentNames,
            'items' => $items,
            'grandTotal' => $grandTotal,
            'base64MooBanLogo' => $base64MooBanLogo,
        ];

        $pdf = PDF::loadView('bills.receipt-full', $data)->setPaper('a4');

        return $pdf->stream();
    }

    public function export(int $transactionId)
    {
        $transaction = Transaction::with([
            'invoice.unit.residence',
            'invoice.billPayeeSetting.residence.propertyManagementUser',
            'invoice.items',
            'invoice.payers.user',
            'paymentMethod',
        ])->findOrFail($transactionId);

        $residentNames = $transaction->invoice->payers
            ->map(function ($payer) {
                return $payer->user?->name ? ucfirst($payer->user->name) : null;
            })
            ->filter() // remove nulls
            ->implode(', ');

        $items = $transaction->invoice->items->where('status', '!=', 6);

        $grandTotal = $items->sum('price');

        $base64MooBanLogo = $transaction?->invoice?->billPayeeSetting?->residence?->propertyManagementUser?->image_base64 ?? null;

        $data = [
            'transaction' => $transaction,
            'residentName' => $residentNames,
            'items' => $items,
            'grandTotal' => $grandTotal,
            'base64MooBanLogo' => $base64MooBanLogo,
        ];

        $pdf = PDF::loadView('bills.receipt-full', $data)->setPaper('a4');

        $fileName = 'receipt-' . $transaction->id . '.pdf';

        return $pdf->download($fileName);
    }
}
