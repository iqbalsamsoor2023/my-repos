<?php

namespace App\Http\Resources\Parcel;

use App\Enums\Parcel\PickupType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pickupEnum = PickupType::tryFrom($this->pickup_type);

        return [
            'id' => $this->id,
            'unit_id' => (int) $this->unit_id,
            'receiver_id' => $this->receiver_id,
            'receiver_name' => $this->receiver_name,
            'pickup_person_contact_no' => $this->pickup_person_contact_no,
            'qr_code' => $this->qr_code,
            'parcel_generated_no' => $this->parcel_generated_no,
            'pickup_person_name' => $this->pickup_person_name,
            'tracking_no' => $this->tracking_no,
            'description' => $this->description,
            'status' => $this->status,
            'pickup_type' => $pickupEnum?->getLabel(),
            'pickup_time' => $this->pickup_time,
            'created_by_mmb_user_id' => $this->created_by_mmb_user_id,
            'created_by_sgoc_user_id' => $this->created_by_sgoc_user_id,
            'image_url' => $this->image_url,
            'image_urls' => $this->image_urls,
            'signature_image_url' => $this->signature_image_url,
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
            'unit' => [
                'unit_number' => $this?->unit?->unit_number,
            ],
            'courier' => [
                'name' => $this?->courier?->name,
            ],
        ];
    }
}
