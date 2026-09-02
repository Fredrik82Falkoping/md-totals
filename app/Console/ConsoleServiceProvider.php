<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Support\Providers\ConsoleServiceProvider as ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * The command bindings for the application.
     */
    protected $commands = [
        \App\Console\Commands\ImportMarkdownsCsv::class, // Lägg till din klass här!
        \App\Console\Commands\CreateUser::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }
}