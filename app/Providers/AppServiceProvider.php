<?php

namespace App\Providers;

use App\Models\User;
use App\Support\TestExecutionGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Pulse\Facades\Pulse;
use Opcodes\LogViewer\Facades\LogViewer;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {

        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTestExecutionGuard();

        Passport::enablePasswordGrant();
        if ($this->app->environment('local')) {
            Mail::alwaysTo('digital@mymooban.co.th');
        }

        LogViewer::auth(function ($request) {
            return $request->user() && $request->user()->hasRole('Super Admin');
        });

        Gate::define('viewPulse', fn (User $user): bool => $user->hasRole('Super Admin'));

        // Pulse swallows its own errors so it never breaks a request; log them
        // so a broken ingest pipeline is still visible.
        Pulse::handleExceptionsUsing(fn (\Throwable $e) => Log::warning('Pulse: '.$e->getMessage()));
    }

    private function registerTestExecutionGuard(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command !== 'test') {
                return;
            }

            $environment = app()->environment();
            $blockedEnvironments = config('test-guard.blocked_environments', TestExecutionGuard::DEFAULT_BLOCKED_ENVIRONMENTS);
            $allowStaging = TestExecutionGuard::toBool(config('test-guard.allow_staging', false));

            if (! TestExecutionGuard::shouldBlock($environment, $blockedEnvironments, $allowStaging)) {
                return;
            }

            throw new RuntimeException(TestExecutionGuard::blockedMessage($environment));
        });
    }
}
