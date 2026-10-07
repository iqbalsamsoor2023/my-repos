<?php

namespace App\Enums\SosManagement;

enum UserActionRequest: int
{
    case CALL_AMBULANCE = 1;
    case CALL_POLICE = 2;
}
