<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SectionHeadingHouseType extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.unit-resource.widgets.section-heading-house-type';

    public static function canView(): bool
    {
        return UnitPolicy::isGlobalAdmin(Auth::user());
    }
}
