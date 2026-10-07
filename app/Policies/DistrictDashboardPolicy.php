<?php

namespace App\Policies;

use App\Models\User;

class DistrictDashboardPolicy
{
    public const VIEW_ABILITY = 'view-district-dashboard';

    /**
     * Determine whether the user can view the district dashboard.
     */
    public function view(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin']);
    }
}
