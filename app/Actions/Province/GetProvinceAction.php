<?php

namespace App\Actions\Province;

use App\Models\Erp\ThailandProvince;

class GetProvinceAction
{
    public function execute($request)
    {
        $provinces = ThailandProvince::query();

        if($request->filled('page')) {
            return $provinces->paginate(25);
        }

        // return double data format for application side compability
        return [
            'data' => $provinces->get()
        ];
    }
}
