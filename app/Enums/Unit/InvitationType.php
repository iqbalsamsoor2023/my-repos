<?php

namespace App\Enums\Unit;

enum InvitationType: int
{
    case OWNER = 1;
    case TENANT = 2;
}
