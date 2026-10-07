<?php

namespace App\Repositories;

use App\Actions\LPR\GetVehicleInfoAction;
use App\Interfaces\LPRRepositoryInterface;

class LPRRepository implements LPRRepositoryInterface
{
    public function create($request)
    {
        $getVehicleInfoAction = new GetVehicleInfoAction;

        return $getVehicleInfoAction->execute($request);
    }
}
