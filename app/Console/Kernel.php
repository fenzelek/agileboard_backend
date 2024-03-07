<?php

namespace App\Console;

use App\Console\Commands\DeleteTempFiles;
use App\Console\Commands\NotificationScheduledDate;
use App\Console\Commands\ImportContractors;
use App\Console\Commands\SendDailyTicketReports;
use App\Console\Commands\SetPackage;
use App\Console\Commands\TrackTime;
use App\Modules\Company\Jobs\ActivateModules;
use App\Modules\Company\Jobs\CleanupClipboard;
use App\Modules\Company\Jobs\ChangeToDefault;
use App\Modules\Company\Jobs\RemindExpiringModules;
use App\Modules\Company\Jobs\RenewSubscription;
use App\Modules\Company\Jobs\RenewSubscriptionMails;
use App\Modules\TimeTracker\Jobs\WaitingFrameActivator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ImportContractors::class,
        TrackTime::class,
        DeleteTempFiles::class,
        NotificationScheduledDate::class,
        SetPackage::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('telescope:prune')->dailyAt('23:00');
        $schedule->job($this->app->make(CleanupClipboard::class))->dailyAt('2:00');
        $schedule->job($this->app->make(RenewSubscription::class))->daily();
        $schedule->job($this->app->make(RenewSubscriptionMails::class))->dailyAt('1:00');
        $schedule->job($this->app->make(ActivateModules::class))->dailyAt('3:00');
        $schedule->job($this->app->make(ChangeToDefault::class))->dailyAt('4:00');
        $schedule->job($this->app->make(RemindExpiringModules::class))->dailyAt('5:00');
//        $schedule->job($this->app->make(WaitingFrameActivator::class))->everyFiveMinutes();

        $schedule->command(TrackTime::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(DeleteTempFiles::class)->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command(NotificationScheduledDate::class)->daily()->withoutOverlapping();
        $schedule->command(SendDailyTicketReports::class, ['--company' => 'company'])
            ->dailyAt('7:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}
