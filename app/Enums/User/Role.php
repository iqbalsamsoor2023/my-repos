<?php

namespace App\Enums\User;

enum Role: int
{
    const SUPER_ADMIN = 1;

    const PROPERTY_MANAGEMENT = 2;

    const SALES_MANAGER = 3;

    const DEVELOPER = 4;

    const RECEPTIONIST = 5;

    const ACCOUNTANT = 6;

    const UNIT_OWNER = 7;

    const UNIT_TENANT = 8;

    const PROPERTY_MANAGEMENT_OPERATION_CENTER = 9;
}
