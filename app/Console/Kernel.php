<?php

namespace App\Console;

use App\Console\Commands\FileUpdate;
use App\Console\Commands\UpdateApiDataCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     * Put the command controller here
     * @var array
     */
    protected $commands = [
        UpdateApiDataCommand::class,
        FileUpdate::class

    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //每天凌晨兩點
        $schedule->command('command:api-update')->cron('0 2 * * *')->withoutOverlapping();
        //每天凌晨四點
        $schedule->command('command:file-update')->cron('0 4 * * *')->withoutOverlapping();

        // 每天凌晨一點
//        $schedule->command('BigBlueOrderAuthorization:Update')->dailyAt('01:00')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
