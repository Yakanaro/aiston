<?php

namespace App\Console;

use App\Jobs\CheckTaskStatusJob;
use App\Models\Task;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Task::query()
                ->whereIn('status', ['new', 'processing'])
                ->orderBy('id')
                ->pluck('id')
                ->each(function ($id) {
                    CheckTaskStatusJob::dispatch((int) $id);
                });
        })->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
