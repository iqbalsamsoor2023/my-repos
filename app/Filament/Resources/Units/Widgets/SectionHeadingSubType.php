<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SectionHeadingSubType extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.unit-resource.widgets.section-heading-sub-type';

    public static function canView(): bool
    {
        return UnitPolicy::isGlobalAdmin(Auth::user());
    }
}
