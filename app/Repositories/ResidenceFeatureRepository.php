<?php

namespace App\Repositories;

use App\Actions\Residence\GetResidenceFeatureAction;

class ResidenceFeatureRepository
{
    public function index($request)
    {
        $getResidenceFeatureAction = new GetResidenceFeatureAction;
        $getResidenceFeatureAction = $getResidenceFeatureAction->execute($request);

        return $getResidenceFeatureAction;
    }
}
