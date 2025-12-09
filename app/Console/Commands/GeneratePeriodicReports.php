<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReportController;
use Illuminate\Console\Command;

/**
 * app/Console/Commands/GeneratePeriodicReports.php
 *
 * Console command om periodieke rapporten automatisch te genereren.
 * Kan handmatig via 'php artisan reports:generate' worden aangeroepen
 * of via een scheduler ingepland.
 */
class GeneratePeriodicReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genereer periodieke rapporten (weekly, monthly, quarterly) of alles als type niet opgegeven.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $controller = new ReportController(app('App\Services\ReportService'));

        if ($type === 'weekly' || !$type) {
            $this->info('Genereren wekelijks rapport...');
            $controller->generateWeekly();
            $this->line('✓ Wekelijks rapport gegenereerd.');
        }

        if ($type === 'monthly' || !$type) {
            $this->info('Genereren maandelijks rapport...');
            $controller->generateMonthly();
            $this->line('✓ Maandelijks rapport gegenereerd.');
        }

        if ($type === 'quarterly' || !$type) {
            $this->info('Genereren kwartaalrapport...');
            $controller->generateQuarterly();
            $this->line('✓ Kwartaalrapport gegenereerd.');
        }

        $this->info('Alle gespecificeerde rapporten zijn gegenereerd.');

        return Command::SUCCESS;
    }
}
