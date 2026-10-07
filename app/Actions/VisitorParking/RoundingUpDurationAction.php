<?php

namespace App\Actions\VisitorParking;

class RoundingUpDurationAction
{
    public function execute($minutesToRound)
    {
        $minuteValue = $minutesToRound % 60;
        $hourValue = floor($minutesToRound / 60);

        if ($minuteValue > 0) {
            $hourValue = $hourValue + 1;
        }

        return $hourValue;
    }
}
