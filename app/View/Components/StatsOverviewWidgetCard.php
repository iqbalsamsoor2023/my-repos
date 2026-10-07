<?php

namespace App\View\Components;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatsOverviewWidgetCard extends Stat
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View
    {
        return view('components.stats-overview-widget-card', $this->data());
    }
}
