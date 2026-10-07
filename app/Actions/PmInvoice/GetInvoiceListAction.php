<?php

namespace App\Actions\PmInvoice;

use App\Models\PmInvoice;

class GetInvoiceListAction
{
    public function execute($request)
    {
        return PmInvoice::query()
            ->with([
                'residence',
                'unit',
                'issuedUser',
                'billings.invoiceType',
                'billings.residence',
                'outstandingInvoices',
            ])
            ->orderBy('invoice_date', 'desc')
            ->when($request->get('residence_id'), function ($query, $residenceId) {
                $query->where('residence_id', $residenceId);
            })
            ->when($request->get('unit_id'), function ($query, $unitId) {
                $query->where('unit_id', $unitId);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->whereIn('status', (array) $request->get('status'));
            })
            ->when($request->get('invoice_date_from'), function ($query, $dateFrom) {
                $query->whereDate('invoice_date', '>=', $dateFrom);
            })
            ->when($request->get('invoice_date_until'), function ($query, $dateUntil) {
                $query->whereDate('invoice_date', '<=', $dateUntil);
            })
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('issue_to_name', 'like', "%{$search}%");
                });
            })
            ->paginate(20);
    }
}
