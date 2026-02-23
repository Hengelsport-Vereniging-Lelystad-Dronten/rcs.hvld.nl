<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Overtreding;
use Illuminate\Support\Facades\DB;

class MigrateLegacyOvertredingen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-overtredingen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migreert bestaande overtredingen naar de nieuwe 7W-datastructuur door de nieuwe velden te vullen op basis van bestaande data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starten van migratie voor legacy overtredingen...');

        // We selecteren alle overtredingen die nog niet gemigreerd zijn.
        // Een lege 'geconstateerd_op' is een betrouwbare indicator.
        // We laden direct de relaties die we nodig hebben (eager loading) voor betere performance.
        $legacyOvertredingen = Overtreding::whereNull('geconstateerd_op')
                                          ->with('controleRonde.water')
                                          ->get();

        if ($legacyOvertredingen->isEmpty()) {
            $this->info('Geen legacy overtredingen gevonden om te migreren. Alles is al up-to-date.');
            return self::SUCCESS;
        }

        $this->info("Totaal {$legacyOvertredingen->count()} overtredingen gevonden om te migreren.");
        $progressBar = $this->output->createProgressBar($legacyOvertredingen->count());
        $progressBar->start();

        $migratedCount = 0;

        // Gebruik een transactie. Als er één overtreding faalt, wordt de hele migratie teruggedraaid.
        DB::transaction(function () use ($legacyOvertredingen, $progressBar, &$migratedCount) {
            foreach ($legacyOvertredingen as $overtreding) {
                $dataToUpdate = [];

                // 1. WANNEER: Gebruik de 'created_at' timestamp als een logische en veilige default.
                $dataToUpdate['geconstateerd_op'] = $overtreding->created_at;

                // 2. WAAR: Haal locatiegegevens op van het gekoppelde water via de controleronde.
                if ($overtreding->controleRonde && $overtreding->controleRonde->water) {
                    $water = $overtreding->controleRonde->water;
                    $dataToUpdate['locatie_details'] = json_encode([
                        'type' => 'water',
                        'id' => $water->id,
                        'naam' => $water->naam,
                    ]);
                }

                // 3. HOE: Stel een veilige standaard in. 'Visueel' is de meest voorkomende.
                // De originele 'details' blijven behouden voor eventuele handmatige correctie.
                $dataToUpdate['constatering_wijze'] = 'visueel';

                // 4. WAAROM & WAARMEE: Deze zijn te moeilijk om betrouwbaar uit de 'details'
                // tekst te parsen. We laten ze leeg (null). De originele 'details' tekst
                // blijft behouden, dus er gaat geen informatie verloren.
                $dataToUpdate['aanleiding'] = null;
                $dataToUpdate['middel'] = null;

                // Update de overtreding in de database
                $overtreding->update($dataToUpdate);

                $migratedCount++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->info("\n\nMigratie voltooid.");
        $this->info("Totaal {$migratedCount} overtredingen succesvol gemigreerd.");

        return self::SUCCESS;

    }
}
