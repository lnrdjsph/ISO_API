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
        $schedule->command('products:update-allocations')->dailyAt('06:00');
        $schedule->command('products:update-allocations')->dailyAt('10:00');
        // $schedule->command('products:update-allocations')->dailyAt('08:35');
        // $schedule->command('products:update-allocations')->dailyAt('08:55');
        // $schedule->command('products:update-allocations')->everyTenMinutes()->when(function () {
        //     $hour = now()->hour;
        //     return $hour == 11; // Only in the 10:00 hour
        // });
        // $schedule->command('products:update-allocations')->dailyAt('12:00');
        $schedule->command('products:update-allocations')->dailyAt('14:45');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        // Load all commands from the Commands folder
        $this->load(__DIR__ . '/Commands');

        // Include console route commands
        require base_path('routes/console.php');
    }
}
