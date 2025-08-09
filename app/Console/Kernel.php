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
      
        // $schedule->command('backup:run')->everyMinute();
      

         //Backup at 8:00 AM
        $schedule->command('backup:run')->dailyAt('8:00')->timezone('Asia/Colombo');

        // Backup at 22:00 PM
        $schedule->command('backup:run')->dailyAt('22:00')->timezone('Asia/Colombo');

        // after 5PM backup
        $schedule->command('backup:clean')->dailyAt('23:00')->timezone('Asia/Colombo');
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
