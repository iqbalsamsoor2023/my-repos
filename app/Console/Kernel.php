<?php

namespace App\Console;

use App\Console\Commands\DeleteTrackerCommand;
use App\Console\Commands\GenerateMasterPassword;
use App\Console\Commands\Visitor\AutoExpiredPreRegisterQR;
use App\Jobs\AggregateVmsDailySummary;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(ScheduleCheckHeartbeatCommand::class)->everyMinute()->environments(['production']);
        $schedule->command(RunHealthChecksCommand::class)->everyMinute()->environments(['production']);
        $schedule->command('app:update-logistic-partner-priority')->dailyAt('00:01');
        $schedule->command(AutoExpiredPreRegisterQR::class)->everyMinute();
        $schedule->command(GenerateMasterPassword::class)->dailyAt('00:01');
        $schedule->command(DeleteTrackerCommand::class)->dailyAt('03:00');
        $schedule->command('data-deletion:notification')->dailyAt('01:00');
        // $schedule->command('data-deletion:pgs')->dailyAt('01:00');
        $schedule->command('export:clean-temp-images')->everyTenMinutes();
        $schedule->command('export:clean-old-parking-exports')->daily();
        // The VMS retention pipeline runs in a fixed order every night, and the three
        // steps must not overlap each other — they all write the same archive tables.
        //
        //   01:00 (1st of month) provision partitions
        //   02:00 archive        visitor_logs -> visitor_logs_archive
        //   04:30 purge          delete expired archive rows + their COS files (PDPA)
        //
        // `onOneServer()` is what stops a second scheduler host starting its own copy;
        // it needs the shared Redis cache, which production has. The archive command
        // additionally takes a MySQL advisory lock, because `withoutOverlapping()`
        // expires after its window while a backlog run can outlast it, and a manual
        // run bypasses the scheduler's mutex entirely.
        $schedule->command('archive:visitor-data-safely --force --retain-days=30 --max-age-days=365 --max-per-run=500000')
            ->dailyAt('02:00')
            ->onOneServer()
            ->withoutOverlapping(180);

        // The only thing allowed to delete personal data: a row also owns COS objects
        // (visitor images, parking vouchers) and `media` rows, and this purges those
        // first, then the row. Nothing else may remove archive rows — see
        // vms:maintain-archive-partitions, which only ever drops ALREADY-EMPTY partitions.
        $schedule->command('data-deletion:vms-archive --force --max-runtime=3600')
            ->dailyAt('04:30')
            ->onOneServer()
            ->withoutOverlapping(120);

        // Keeps the archive tables partitioned by month and hands back the disk the
        // purge left behind. Routine runs are near-instant because partitions are
        // provisioned six months ahead, so `pmax` stays empty. The FIRST run is the
        // exception: it rewrites whatever `pmax` already accumulated and blocks writes
        // to the table while it does — run that one by hand in a maintenance window.
        $schedule->command('vms:maintain-archive-partitions --ensure-months=6 --drop-empty')
            ->monthlyOn(1, '01:00')
            ->onOneServer()
            ->withoutOverlapping(120);

        // A silent failure here left DDI data stale for weeks.
        $schedule->command('residence:sync-stats-view')
            ->dailyAt('05:00')
            ->withoutOverlapping(60)
            ->onFailure(fn () => Log::error('residence:sync-stats-view failed'));

        $schedule->command('unit:sync-stats-view')
            ->dailyAt('05:10')
            ->withoutOverlapping(60);

        $schedule->command('unit:sync-widget-aggregates')
            ->everyFiveMinutes()
            ->withoutOverlapping(10);

        $schedule->command('unit-user:sync-widget-aggregates')
            ->everyFiveMinutes()
            ->withoutOverlapping(10);

        $schedule->command('unit-user:sync-stats-view')
            ->dailyAt('05:20')
            ->withoutOverlapping(60);

        $schedule->command('vehicle:sync-stats-view')
            ->dailyAt('05:30')
            ->withoutOverlapping(60);

        $schedule->command('vehicle:sync-widget-aggregates')
            ->everyFiveMinutes()
            ->withoutOverlapping(10);

        $schedule->command('parcel:sync-stats-view')
            ->dailyAt('05:40')
            ->withoutOverlapping(60);

        $schedule->command('parcel:sync-widget-aggregates')
            ->everyFiveMinutes()
            ->withoutOverlapping(10);

        $schedule->call(function () {
            AggregateVmsDailySummary::dispatch(now()->subDay()->toDateString(), false)
                ->onQueue('StatsSync');
        })->dailyAt('04:00')
            ->name('aggregate-vms-daily-summary')
            ->withoutOverlapping(60);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
