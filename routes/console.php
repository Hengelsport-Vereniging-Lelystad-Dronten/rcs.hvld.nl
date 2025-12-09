<?php

/**
 * routes/console.php
 *
 * Console commands die via `php artisan` beschikbaar zijn. Gebruik dit bestand
 * om korte CLI-commando's te registreren die handig zijn voor onderhoud/tests.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
