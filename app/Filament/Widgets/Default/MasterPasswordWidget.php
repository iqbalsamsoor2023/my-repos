<?php

namespace App\Filament\Widgets\Default;

use App\Models\MasterPassword;
use Filament\Widgets\Widget;

class MasterPasswordWidget extends Widget
{
    protected string $view = 'filament.widgets.default.master-password-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 1;

    public ?string $masterPassword = null;

    public static function canView(): bool
    {
        return auth()->guard()->user()?->hasRole('Super Admin') ?? false;
    }

    public function mount(): void
    {
        $this->masterPassword = MasterPassword::query()->value('password');
    }
}
