<?php

use Illuminate\Support\Facades\Schedule;

// Wekelijks rapport op maandagochtend (over vorige week)
Schedule::command('report:send last_week bestuur@hvld.nl')->weeklyOn(1, '08:00')->withoutOverlapping()->timezone('Europe/Amsterdam');

// Maandelijks rapport op de 1e van de maand (over vorige maand)
Schedule::command('report:send last_month bestuur@hvld.nl')->monthlyOn(1, '08:00')->withoutOverlapping()->timezone('Europe/Amsterdam');
