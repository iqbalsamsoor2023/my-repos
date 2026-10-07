<?php

namespace App\Actions\District;

use App\Models\Erp\ThailandDistrict;

class GetDistrictAction
{
    public function execute($request)
    {
        $district = ThailandDistrict::query();

        if (isset($request->province_id)) {
            $district = $district->where('province_id', $request->province_id);
        }

        return $district->paginate(25);
    }
}
