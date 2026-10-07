<?php

namespace App\Enums\Parking;

enum DiscountType: int
{
    case NO_DISCOUNT_COUPON = 0;
    case PRICE = 1;
    case TIME = 2;
}
