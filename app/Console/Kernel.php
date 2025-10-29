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
      
        //  //Backup at 8:00 AM
        // $schedule->command('backup:run')->dailyAt('08:00')->timezone('Asia/Colombo');

        // // Backup at 22:00 PM
        // $schedule->command('backup:run')->dailyAt('22:00')->timezone('Asia/Colombo');

        // // after 5PM backup
        // $schedule->command('backup:clean')->dailyAt('23:00')->timezone('Asia/Colombo');

         // after 11PM backup
        $schedule->command('backup:clean')->dailyAt('23:00')->timezone('Asia/Colombo');

        // Update sales rep assignment statuses based on dates (runs every hour)
        $schedule->command('sales-rep:update-status')->hourly()->timezone('Asia/Colombo');
        
        // Alternative: run it every day at midnight
        // $schedule->command('sales-rep:update-status')->dailyAt('00:01')->timezone('Asia/Colombo');
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
