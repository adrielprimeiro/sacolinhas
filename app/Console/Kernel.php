<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inter:sync --days=14')->hourly();
        $schedule->command('mp:sync --days=14')->hourly();
        $schedule->command('me:sync')->everyThreeHours();
        $schedule->command('portal:cancelar-expirados')->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}