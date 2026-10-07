<?php

namespace App\Enums\Residence;

enum Features: int
{
    case INBOX = 1;
    case VISITOR = 2;
    case CLAIM = 3;
    case PARCEL = 4;
    case BOOKING = 5;
    case DEVELOPER = 6;
    case CONTACT = 7;
    case BILLING = 8;
    case PARKING_FEE_BASIC = 9;
    case PARKING_FEE_PRO = 10;
    case SALES_MANAGEMENT = 11;
    case RECEPTION_MANAGEMENT = 12;
    case RESALE_AND_TENANCY = 13;
    case FACILITIES_MANAGEMENT = 14;
    case PROPERTY_MANAGEMENT = 15;
    case CLEANLINESS_MANAGEMENT = 16;
    case SECURITY_MANAGEMENT = 17;
    case ACCOUNTING_MANAGEMENT = 18;
}
