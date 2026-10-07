<?php

namespace App\Http\Resources\Billing;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'billing_number' => $this->billing_number,
            'billing_date' => Carbon::parse($this->billing_date)->format('d M Y'),
            'service_duration_from' => Carbon::parse($this->service_duration_from)->format('d M Y'),
            'service_duration_until' => Carbon::parse($this->service_duration_until)->format('d M Y'),
            'description' => $this->invoiceType?->name ?? 'N/A',
            'grand_total' => number_format((float) $this->total, 2),
            'remarks' => $this->remarks,
        ];
    }
}
