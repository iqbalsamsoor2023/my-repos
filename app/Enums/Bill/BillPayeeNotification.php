<?php

namespace App\Enums\Bill;

enum BillPayeeNotification: string
{
    case ALL_RESIDENT = 'All';
    case MAIN_OWNER = 'Main Owner';
    case MAIN_TENANT = 'Main Tenant';
}
