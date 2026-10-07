<?php

namespace App\Enums\Visitor;

enum VisitorPurpose: int
{
    case RECEIVER_DELIVERY = 1;
    case DROP_OFF_OR_PICKUP = 2;
    case CONTRACTOR_WORKER = 3;
    case VISITOR_PARKING = 4;
    case VIP = 5;
}
