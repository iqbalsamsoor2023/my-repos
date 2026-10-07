<?php

namespace App\Enums\Parking;

enum RateMode: int
{
    case PER_HOUR = 1;
    case PER_DAY = 2;
}
