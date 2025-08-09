<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
      
        $schedule->command('backup:run')->everyMinute();
        $schedule->command('backup:run')->everyMinute();

         //Backup at 10:00 AM
        // $schedule->command('backup:run')->dailyAt('10:00')->timezone('Asia/Colombo');

        // // Backup at 5:00 PM
        // $schedule->command('backup:run')->dailyAt('17:00')->timezone('Asia/Colombo');

        // // after 5PM backup
        // $schedule->command('backup:clean')->dailyAt('18:00')->timezone('Asia/Colombo');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
