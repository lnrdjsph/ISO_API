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
        // Product allocation sync
        $schedule->command('products:update-allocations')->dailyAt('08:00');
        $schedule->command('products:update-allocations')->dailyAt('10:00');
        $schedule->command('products:update-allocations')->dailyAt('14:45');

        // Order Processing Agent
        $schedule->command('order:process', ['--limit' => 100])
            ->everyFiveMinutes()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::channel('order_agent')->info('Scheduled order processing executed successfully');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::channel('order_agent')->error('Scheduled order processing failed');
            });

        // Order SLA Check
        $schedule->command('order:check-sla')
            ->hourly()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::channel('order_agent')->info('Scheduled SLA check executed');
            });
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
