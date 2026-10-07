<?php

namespace App\Actions\Subdistrict;

use App\Models\Erp\ThailandSubDistrict;
use Illuminate\Http\Request;

class GetSubdistrictAction
{
    public function execute(Request $request)
    {
        $subdistricts = ThailandSubDistrict::query();

        if (isset($request->district_id)) {
            $subdistricts = $subdistricts->where('district_id', $request->district_id);
        }

        if (isset($request->has_pagination) && ($request->has_pagination == false)) {
            return $subdistricts->get();
        }

        return $subdistricts->paginate(25);
    }
}
