<?php

namespace App\Enums\UnitUser;

enum UnitUserRoleType: int
{
    case OWNER = 1;
    case TENANT = 0;
}
