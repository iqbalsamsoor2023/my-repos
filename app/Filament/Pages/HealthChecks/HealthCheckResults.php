<?php

namespace App\Filament\Pages\HealthChecks;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\ResultStores\ResultStore;

class HealthCheckResults extends Page
{
    protected $listeners = ['refresh-component' => '$refresh'];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    protected string $view = 'filament-spatie-health::pages.health-check-results';

    protected function getHeaderActions(): array
    {
        return [
            Action::make(__('filament-spatie-health::health.pages.health_check_results.buttons.refresh'))->action('refresh'),
        ];
    }

    public function getHeading(): string
    {
        return __('filament-spatie-health::health.pages.health_check_results.heading');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('menu.setting_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-spatie-health::health.pages.health_check_results.navigation.label');
    }

    protected function getViewData(): array
    {
        $checkResults = app(ResultStore::class)->latestResults();

        return [
            'lastRanAt' => new Carbon($checkResults?->finishedAt),
            'checkResults' => $checkResults,
        ];
    }

    public function refresh(): void
    {
        Artisan::call(RunHealthChecksCommand::class);

        $this->dispatch('refresh-component');

        Notification::make()
            ->title(__('filament-spatie-health::health.pages.health_check_results.notifications.results_refreshed'))
            ->success()
            ->send();

        // $this->emitSelf('refreshComponent');
    }
}
