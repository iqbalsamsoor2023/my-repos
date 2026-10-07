<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidenceBankAccountResource extends JsonResource
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
            'bank_id' => $this->bank_id,
            'bank_name' => $this->bank->name,
            'bank_name_th' => $this->bank->name_th,
            'bank_logo' => $this->bank->getFirstMediaUrl('bank') ?: 'https://dashboard.mymooban.co.th/images/no-image.png',
            'bank_account_name' => $this->payee_account_name,
            'bank_account_name_th' => $this->payee_account_name_th,
            'bank_account_no' => $this->payee_account_number,
        ];
    }
}
