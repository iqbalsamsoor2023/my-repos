<?php

namespace App\Enums\User;

enum RoleType: string
{
    case SUPER_ADMIN = 'Super Admin';
    case ADMIN = 'Admin';
    case PROPERTY_MANAGEMENT = 'Property Management';
    case SECURITY_GUARD = 'Security Guard';
    case RECEPTIONIST = 'Receptionist';
    case COMMUNITY_COMMITTEE = 'Community Committee';
    case RESIDENT = 'Resident';
    case ACCOUNTANT = 'Accountant';
    case DEVELOPER = 'Developer';
    case UNIT_OWNER = 'Unit Owner';
    case UNIT_TENANT = 'Unit Tenant';
    case PROPERTY_MANAGEMENT_OPERATION_CENTER = 'Property Management Operation Center';
    case FACILITIES_MANAGEMENT = 'Facilities Management';
    case CLEANLINESS_MANAGEMENT = 'Maid';
    case SALES_MANAGEMENT = 'Sales Management';
    case RESALES_AND_TENANCY_MANAGEMENT = 'Re-sales & Tenancy Management';
    case GUARD_TALK = 'Guard Talk';
    case TECHNICIAN = 'Technician';
    case TECHNICIAN_ADMIN = 'Technician Admin';

    case PM = 'pm';
    case SC = 'sc';
    case RC = 'rc';
    case AC = 'ac';
    case PMOC = 'pmoc';
    case FM = 'tc';
    case MD = 'md';
    case SM = 'sm';
    case RTM = 'rtm';
    case GT = 'gt';

    case DOMAIN_MAIL = '@mymooban.co.th';
    case SGOC_DOMAIN_MAIL = '@mysgoc.com';
}
