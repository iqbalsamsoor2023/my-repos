<?php

namespace App\Enums\ActivationModule;

enum ModuleType: int
{
    case INBOX = 1;
    case VISITOR = 2;
    case CLAIM = 3;
    case PARCEL = 4;
    case BOOKING = 5;
    case DEVELOPER = 6;
    case CONTACT = 7;
    case BILLING = 8;
    case BRAND = 9;
    case PURPOSE_OF_VISIT = 10;
    case CONTACT_NUMBER = 11;
    case TEMPERATURE = 12;
    case COMPANY_NAME = 13;
    case PASSENGER = 14;
    case REMARK = 15;
    case COLOR = 16;
    case PDPA = 17;
    case VISITOR_PHOTO = 18;
    case SCAN_VISITOR_CARD = 19;
    case VS_VISITOR_CARD = 20;
    case VS_VISITOR_NAME = 21;
    case VS_VEHICLE_PLATE_NO = 22;
    case VS_PROVINCE_OF_VEHICLE = 23;
    case VS_BRAND = 24;
    case VS_COLOR = 25;
    case VS_PURPOSE_OF_VISIT = 26;
    case VS_VEHICLE_TYPE = 27;
    case VS_CONTACT_AT = 28;
    case VS_CONTACT_NUMBER = 29;
    case VS_TEMPERATURE = 30;
    case VS_COMPANY_NAME = 31;
    case VS_PASSENGER = 32;
    case VS_REMARK = 33;
    case VS_STAMP = 34;
    case VS_QR_SCAN_OUT = 35;
    case VS_MOOBAN_LOGO = 36;
    case VEHICLE_PHOTO = 37;
    case PARKING_FEE = 38;
    case FOOD_AND_PARCEL = 39;
    case PARKING = 40;
    case VS_ONLY_STAMP = 41;
    case VS_ONLY_SIGNATURE = 42;
}
