<?php

namespace App\Observers;

use App\Models\VisitorSetting;
use Illuminate\Support\Facades\Cache;

class VisitorSettingObserver
{
    public function saved(VisitorSetting $visitorSetting)
    {
        Cache::tags([
            'visitor_settings',
            "residence:{$visitorSetting->residence_id}"
        ])->flush();
    }

    public function deleted(VisitorSetting $visitorSetting)
    {
        Cache::tags([
            'visitor_settings',
            "residence:{$visitorSetting->residence_id}"
        ])->flush();
    }
}
