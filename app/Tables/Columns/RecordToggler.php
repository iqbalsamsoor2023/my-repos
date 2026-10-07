<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;
use Filament\Forms\Components\Concerns\HasToggleColors;
use Filament\Forms\Components\Concerns\HasToggleIcons;

class RecordToggler extends Column
{
    use HasToggleColors;
    use HasToggleIcons;

    protected string $view = 'tables.columns.record-toggler';
}
