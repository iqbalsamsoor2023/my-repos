<?php

namespace App\Providers\Filament;

use App\Http\Middleware\LanguageMiddleware;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Config;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use App\Filament\Livewire\DatabaseNotifications;
use WatheqAlshowaiter\FilamentStickyTableHeader\StickyTableHeaderPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(false)
            ->loginRouteSlug('login')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([])
            ->widgets([])
            ->userMenuItems(array_merge(
                [
                    'profile' => Action::make('profile')
                        ->label(fn (): string => auth()->guard()->user()?->name ?? '')
                        ->url(fn (): string => route('filament.admin.resources.profiles.edit', auth()->guard()->id())),
                ],
                $this->getLanguageMenuItems()
            ))
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('menu.pog_and_user_tutorial'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.sales_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.re_sales_tenancy'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.big_data_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.asset_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.security_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.operation_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.maintenance_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.accounting_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.communication_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.user_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.administrator_management'))->collapsible(),
                NavigationGroup::make()->label(fn (): string => __('menu.setting_management'))->collapsible(),
            ])
            ->plugin(
                StickyTableHeaderPlugin::make(),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'th']),
            )
            ->databaseNotifications(livewireComponent: DatabaseNotifications::class)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LanguageMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->globalSearch(false)
            ->renderHook(
                    PanelsRenderHook::BODY_START,
                    fn () => view('filament.watermark')
                );
    }

    /*
    |--------------------------------------------------------------------------
    | Language Menu
    |--------------------------------------------------------------------------
    */
    protected function getLanguageMenuItems(): array
    {
        $languages = Config::get('languages');
        $menuItems = [];

        foreach ($languages as $lang => $language) {
            $menuItems[] = MenuItem::make()
                ->label($language)
                ->visible(fn (): bool => auth()->guard()->user()?->lang !== $lang)
                ->url(fn () => route('lang.switch', $lang))
                ->icon('heroicon-o-language');
        }

        return $menuItems;
    }
}
