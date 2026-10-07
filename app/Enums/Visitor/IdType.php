<?php

namespace App\Enums\Visitor;

enum IdType: int
{
    case IC = 1;
    case PASSPORT = 2;
    case DRIVING_LICENSE = 3;
    case OTHER = 4;
}
