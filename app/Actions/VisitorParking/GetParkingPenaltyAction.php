<?php

namespace App\Actions\VisitorParking;

class GetParkingPenaltyAction
{
    public function execute($result, $is_penalty, $calculation)
    {
        if ($is_penalty == 1) {
            $result = $calculation['penalty'];
        }

        return $result;
    }
}
