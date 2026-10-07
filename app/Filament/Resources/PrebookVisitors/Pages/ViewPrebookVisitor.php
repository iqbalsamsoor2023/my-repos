<?php

namespace App\Filament\Resources\PrebookVisitors\Pages;

use App\Enums\Vehicle\VehicleColor;
use App\Filament\Resources\PrebookVisitors\PrebookVisitorResource;
use App\Models\Erp\ThailandProvince;
use App\Models\VehicleBrand;
use Filament\Resources\Pages\ViewRecord;

class ViewPrebookVisitor extends ViewRecord
{
    protected static string $resource = PrebookVisitorResource::class;

    public function getTitle(): string
    {
        return __('menu.view_prebook_helpdesk');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['residence_id'] = $this->record->unit->residence_id;

        $visitor_purpose = $data['visitor_purpose'];
        $visitor_purpose_list = ['Receive/Delivery', 'Drop Off/Pick Up', 'Contractor/Worker', 'Visitor Parking', 'VIP'];

        if (in_array($visitor_purpose, $visitor_purpose_list) == false) {
            $data['visitor_purpose'] = 'Other';
            $data['visitor_purpose_other'] = $this->record->visitor_purpose;
        }

        $vehicleInfo = $data['vehicle_info'] ?? [];
        $data['vehicle_plate_no'] = $vehicleInfo['lp_number'] ?? null;

        if (! empty($vehicleInfo['province'])) {
            // Extract "th-75" part and get the code
            if (preg_match('/^th-(\d+):/', $vehicleInfo['province'], $matches)) {
                $provinceCode = $matches[1];

                $province = ThailandProvince::where('code', $provinceCode)->first();
                if ($province) {
                    $data['province_id'] = $province->id;
                }
            }
        }

        if (! empty($vehicleInfo['vehicle_brand'])) {
            $locale = app()->getLocale();

            $brand = VehicleBrand::where(
                $locale === 'th' ? 'name_th' : 'name',
                $vehicleInfo['vehicle_brand']
            )->first();

            if ($brand) {
                $data['vehicle_brand_id'] = $brand->id;
            }
        }

        if (! empty($vehicleInfo['vehicle_color'])) {
            foreach (VehicleColor::cases() as $color) {
                if ($color->label() === $vehicleInfo['vehicle_color']) {
                    $data['vehicle_color'] = $color->colorCode();
                    break;
                }
            }
        }

        return $data;
    }
}
