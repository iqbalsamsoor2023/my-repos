<?php

namespace App\Enums\Unit;

enum PmocType: int
{
    case ART_GALLERY = 1;
    case COMMUNITY_MALL = 2;
    case HOSPITAL = 3;
    case HOTEL = 4;
    case OFFICE_BUILDING = 5;
    case RELIGIOUS_ORGANIZATION = 6;
    case SCHOOL = 7;
    case SHOPPING_MALL = 8;
    case SHOWROOM = 9;
    case SPORT_CLUB = 10;
    // later delete, already have subtype enum
}
