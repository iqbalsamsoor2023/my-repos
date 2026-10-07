<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck;

class HealthCheckServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $healthChecks = [
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(400)
                ->failWhenMoreConnectionsThan(550),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(95),
            CacheCheck::new(),
            ScheduleCheck::new(),
            SecurityAdvisoriesCheck::new(),
            OptimizedAppCheck::new(),
        ];

        if (config('app.env') === 'production') {
            $healthChecks[] = DebugModeCheck::new();
        }

        Health::checks($healthChecks);
    }
}
