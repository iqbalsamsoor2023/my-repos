<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'home_id' => isset($this->invoice->unit) ? $this->invoice->unit->home_id : null,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'vat' => $this->vat,
            'status' => $this->status,
        ];
    }
}
