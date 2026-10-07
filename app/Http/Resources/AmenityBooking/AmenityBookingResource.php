<?php

namespace App\Http\Resources\AmenityBooking;

use App\Models\ResidenceAmenityOption;
use App\Enums\FacilityAndAmenity\AmenityBookingStatusEnum;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AmenityBookingResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language');
        $isThai = strtolower($lang) === 'th';

        $amenity = $this->amenityBookable;
        $isOption = $this->amenity_bookable_type === ResidenceAmenityOption::class;

        $mainAmenity = $isOption
            ? $amenity?->residenceAmenity?->facilityAndAmenity
            : $amenity?->facilityAndAmenity;

        $amenityName = $isThai ? $mainAmenity?->name_in_thai : $mainAmenity?->name;
        $amenitySubName = $isOption ? ($isThai ? $amenity?->name_in_thai : $amenity?->name) : null;
        $iconUrl = $mainAmenity?->icon_url;

        return [
            'id' => $this->id,
            'booking_number' => $this->ref_no,
            'amenity' => [
                'id' => $this->amenity_bookable_id,
                'type' => class_basename($this->amenity_bookable_type),
                'amenity_name' => $amenityName,
                'amenity_sub_name' => $amenitySubName,
            ],
            'status' => AmenityBookingStatusEnum::from($this->status)->getLabel(),
            'booking_date' => $this->start_at
                ? Carbon::parse($this->start_at)->translatedFormat('l, F d, Y')
                : null,
            'booking_time' => ($this->start_at && $this->end_at)
                ? Carbon::parse($this->start_at)->format('g:i A').' - '.Carbon::parse($this->end_at)->format('g:i A')
                : null,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'unit' => [
                'id' => $this->unit?->id,
                'unit_number' => $this->unit?->unit_number,
            ],
            'icon_url' => $iconUrl,
            'qr_code' => 'data:image/png;base64,'.$this->generateQrCode($this->ref_no),
        ];
    }

    protected function generateQrCode(string $value): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new ImagickImageBackEnd
        );

        $writer = new Writer($renderer);

        return base64_encode($writer->writeString($value));
    }
}
