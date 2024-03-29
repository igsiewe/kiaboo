<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        //copier le fichier laravel.log tous les jours à minuit
        $schedule->exec('sudo cp ./../../storage/logs/laravel.log ./../../storage/logs/laravel_$(date +\%Y\%m\%d\%H%M%S).log')->everyMinute();
        $schedule->exec('sudo rm -rf ./../../storage/logs/laravel.log')->everyMinute();
    //    $schedule->exec('touch ./../../storage/logs/laravel.log')->everyMinute();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
