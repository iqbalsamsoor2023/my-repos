<?php

namespace App\Filament\Resources\Vehicles\Widgets;

use App\Policies\VehiclePolicy;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SectionHeadingCar extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.vehicle-resource.widgets.section-heading-car';

    public static function canView(): bool
    {
        return VehiclePolicy::hasDashboardAccess(Auth::user());
    }
}
