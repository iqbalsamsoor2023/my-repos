<?php

namespace App\Actions\WarrantySetting;

use App\Models\WarrantySetting;
use Illuminate\Http\Request;

class GetWarrantySettingAction
{
    public function execute(Request $request)
    {
        $warranty_setting = WarrantySetting::with('residence');

        if (isset($request->residence_id)) {
            $warranty_setting = $warranty_setting->where('residence_id', $request->residence_id);
        }

        return $warranty_setting->orderBy('id', 'DESC')->paginate(20);
    }
}
