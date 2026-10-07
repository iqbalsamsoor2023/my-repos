<?php

namespace App\Repositories;

use App\Actions\ThaiNationalIDOCR\GetFrontIdInfoAction;
use App\Interfaces\ThaiNationalIDOCRInterface;

class ThaiNationalIDOCRRepository implements ThaiNationalIDOCRInterface
{
    public function frontSide($request)
    {
        $getFrontIDInfoAction = new GetFrontIdInfoAction;

        return $getFrontIDInfoAction->execute($request);
    }
}
