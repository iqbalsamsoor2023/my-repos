<?php

namespace App\Actions\VisitorSetting;

use App\Models\VisitorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GetVisitorSettingAction
{
    public function execute(Request $request)
    {
        $residenceId = $request->residence_id ?? 'all';

        $cacheKey = "visitor_settings:{$residenceId}:page:" . request('page', 1);

        return Cache::tags(['visitor_settings', "residence:{$residenceId}"])
            ->rememberForever($cacheKey, function () use ($request) {

                $visitorSettings = VisitorSetting::query();

                if (isset($request->residence_id)) {
                    $visitorSettings->where('residence_id', $request->residence_id);
                }

                return $visitorSettings->paginate(20);
            });
    }
}
