<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Default\MasterPasswordWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            MasterPasswordWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
