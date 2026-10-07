<?php

use App\Enums\Vehicle\VehicleColor;
use App\Models\Erp\ThailandProvince;
use App\Models\User;
use App\Models\VehicleBrand;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

function sendNotificationToDevice(User $user, $notification_class)
{
    if (isset($user)) {
        return Notification::send($user, $notification_class);
    }
}

function updateVehicleInfo(callable $set, callable $get): void
{
    $province = ThailandProvince::find($get('province_id'));
    $brand = VehicleBrand::find($get('vehicle_brand_id'));
    $colorCode = $get('vehicle_color');

    $colorLabel = collect(VehicleColor::cases())
        ->firstWhere(fn ($color) => $color->colorCode() === $colorCode)?->label();

    $vehicleInfo = [
        'province' => $province
            ? 'th-'.$province->code.':'.$province->name_in_english.' ('.$province->name_in_thai.')'
            : null,
        'lp_number' => $get('vehicle_plate_no'),
        'vehicle_brand' => $brand
            ? (App::getLocale() === 'th' ? $brand->name_th : $brand->name)
            : null,
        'vehicle_color' => $colorLabel ?? null,
        'vehicle_model' => $get('vehicle_model') ?: 'N/A',
    ];

    $set('vehicle_info', $vehicleInfo);
}
