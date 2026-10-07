<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class GenerateInvoiceFileDataAction
{
    public function execute(Invoice $invoice)
    {
        foreach ($invoice->payers as $payer) {
            $payers[] = isset($payer->user) ? ucfirst($payer->user->name) : null;
        }

        $data = [
            'invoice' => $invoice,
            'residentName' => isset($payers) ? implode(', ', $payers) : '-',
            'month' => date('m', strtotime($invoice->bill_date)),
            'bankAccountNo' => $invoice->billPayeeSetting->accounts[0]->payee_account_number,
            'bankName' => $invoice->billPayeeSetting->accounts[0]->bank->name_th.' ('.$invoice->billPayeeSetting->accounts[0]->bank->name.')',
            'base64QrImage' => $invoice->billPayeeSetting->image_base64 ?? null,
            'base64MooBanLogo' => $invoice->billPayeeSetting->residence->propertyManagementUser->image_base64 ?? null,
        ];

        return $data;
    }
}
