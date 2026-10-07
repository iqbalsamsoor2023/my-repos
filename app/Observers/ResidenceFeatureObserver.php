<?php

namespace App\Observers;

use App\Models\ResidenceFeature;
use Illuminate\Support\Facades\Cache;


class ResidenceFeatureObserver
{
    public function saved(ResidenceFeature $brand)
    {
        Cache::tags(['activation_modules'])->flush();
    }

    public function deleted(ResidenceFeature $brand)
    {
        Cache::tags(['activation_modules'])->flush();
    }
}
