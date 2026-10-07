<?php

namespace App\Filament\Resources\Visitors\Widgets\Concerns;

use App\Enums\User\RoleType;
use Illuminate\Support\Facades\Auth;

trait ResolvesVmsResidence
{
    protected function pmResidenceId(): ?int
    {
        $user = Auth::user();

        if ($user?->hasRole(RoleType::PROPERTY_MANAGEMENT->value)) {
            return get_residence_by_property_management($user->id)?->id;
        }

        return null;
    }

    protected function isPmView(): bool
    {
        return Auth::user()?->hasRole(RoleType::PROPERTY_MANAGEMENT->value) ?? false;
    }
}
