<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kör importen en gång om dagen klockan 03:37 (nattetid är oftast säkrast för API-anrop)
Schedule::command('md:import-api')
    ->dailyAt('03:37') 
    ->withoutOverlapping() // Förhindrar att en ny startas om den förra ännu inte är klar
    ->emailOutputOnFailure(env('MAIL_ADMIN_ADDRESS')); 
