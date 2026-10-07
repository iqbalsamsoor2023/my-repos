<?php

namespace App\Http\Resources\PmInvoice;

use App\Http\Resources\Billing\BillingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmInvoiceResource extends JsonResource
{
    protected $includeBillings = true;

    public function withoutBillings()
    {
        $this->includeBillings = false;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date?->format('d M Y'),
            'due_date' => $this->due_date?->format('d M Y'),
            'unit' => [
                'id' => $this->unit?->id,
                'unit_number' => $this->unit?->unit_number,
            ],
            'grand_total' => number_format((float) $this->grand_total, 2),
            'outstanding_amount' => number_format((float) $this->outstanding_amount, 2),
            'status' => $this->status,
            'status_name' => $this->status?->getLabel(),
        ];

        if ($this->includeBillings) {
            $data['billings'] = BillingResource::collection($this->billings ?? []);
        }

        if(!$this->outstandingInvoices->isEmpty()) {
            $data['outstanding_invoices'] =  $this->outstandingInvoices?->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date?->format('d M Y'),
                    'due_date' => $invoice->due_date?->format('d M Y'),
                    'grand_total' => number_format((float) $invoice->grand_total, 2),
                    'outstanding_amount' => number_format((float) $invoice->outstanding_amount, 2),
                    'status' => $invoice->status,
                    'status_name' => $invoice->status?->getLabel(),
                ];
            }) ?? [];
        }
        return $data;
    }
}
