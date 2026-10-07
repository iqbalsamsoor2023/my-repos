<?php

namespace App\Enums\UnitUser;

enum EmailStatusType: int
{
    case VERIFIED = 1;
    case PENDING = 2;
}
