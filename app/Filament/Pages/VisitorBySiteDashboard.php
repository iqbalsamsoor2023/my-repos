<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Default\Vbs\VbsFilterWidget;
use App\Filament\Widgets\Default\Vbs\VisitorBySiteTableWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class VisitorBySiteDashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 10;

    protected static string $routePath = 'visitor-by-site';

    public static function getNavigationGroup(): ?string
    {
        return __('menu.big_data_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.visitor_by_site');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            VbsFilterWidget::class,
            VisitorBySiteTableWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 12;
    }
}
