<?php

namespace App\Repositories;

use App\Actions\District\GetDistrictAction;

class DistrictRepository
{
    public function index($request)
    {
        $districtAction = new GetDistrictAction;
        $district = $districtAction->execute($request);

        return $district;
    }
}
