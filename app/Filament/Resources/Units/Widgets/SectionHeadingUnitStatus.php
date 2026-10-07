<?php

namespace App\Filament\Resources\Units\Widgets;

use App\Policies\UnitPolicy;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SectionHeadingUnitStatus extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.unit-resource.widgets.section-heading-unit-status';

    public static function canView(): bool
    {
        return UnitPolicy::isPropertyManager(Auth::user());
    }
}
