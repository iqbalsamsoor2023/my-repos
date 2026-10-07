<?php

namespace App\Repositories;

use App\Actions\Province\GetProvinceAction;

class ProvinceRepository
{
    public function index($request)
    {
        $provinceAction = new GetProvinceAction;
        $province = $provinceAction->execute($request);

        return $province;
    }
}
