<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('products:update-allocations')
            ->twiceDaily(6, 12)
            ->timezone('Asia/Manila'); // force Philippine timezone
            
        $schedule->command('products:update-allocations')
            ->cron('0 10 * * *')      // 1:35 PM
            ->timezone('Asia/Manila');
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        // Load all commands from the Commands folder
        $this->load(__DIR__.'/Commands');

        // Include console route commands
        require base_path('routes/console.php');
    }
}
