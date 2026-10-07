<?php

namespace App\Observers;

use App\Models\VehicleBrand;
use Illuminate\Support\Facades\Cache;


class BrandObserver
{
    public function saved(VehicleBrand $brand)
    {
        Cache::tags(['vehicle_brands'])->flush();
    }

    public function deleted(VehicleBrand $brand)
    {
        Cache::tags(['vehicle_brands'])->flush();
    }
}
