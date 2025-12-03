<?php

namespace App\Services;

use App\Models\Overtreding;
use App\Models\OvertredingType;
use App\Models\Strafmaat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // Log hersteld voor debuggen

/**
 * RecidiveCheckService
 * Bevat de bedrijfslogica voor het controleren van herhaalde overtredingen.
 * De check bepaalt de geadviseerde strafmaat door de historie van de visser
 * te bekijken en, indien nodig, de strafmaat te escaleren.
 *
 * FIX: De databasequery is uiterst robuust gemaakt om rekening te houden met
 * zowel VARCHAR-waarden (met voorloopnullen) als INTEGER-waarden in de kolom
 * 'vispasnummer', wat de oorzaak is van de historie_count = 0.
 *
 * NIEUWE LOGICA: Bij de 3e overtreding (historie_count >= 2) wordt direct gezocht
 * naar de Strafmaat met code 'PV' (Proces-Verbaal) en deze wordt geadviseerd.
 *
 * BELANGRIJK: De output sleutel is TIJDELIJK teruggezet naar 'bericht'
 * om de 500 fout in RecidiveController.php op te lossen.
 *
 * @package App\Services
 */
class RecidiveCheckService
{
    // De periode waarbinnen eerdere overtredingen als 'recidive' worden beschouwd (standaard 12 maanden)
    private const RECIDIVE_PERIODE_MAANDEN = 12;

    /**
     * Controleert de historie van de visser en bepaalt de geadviseerde strafmaat.
     *
     * @param string $vispasNummer Het unieke VISpas nummer van de gecontroleerde visser.
     * @param string $overtredingCode De code van de overtreding (om de default ID op te halen).
     * @param int $lookbackMonths Het aantal maanden terug om te controleren (optioneel).
     * @return array Bevat de geadviseerde Strafmaat ID, de recidive status, en een statusbericht.
     * @throws \Exception Als de overtreding code onbekend is of als er geen default strafmaat is gekoppeld.
     */
    public function checkStatus(string $vispasNummer, string $overtredingCode, int $lookbackMonths = self::RECIDIVE_PERIODE_MAANDEN): array
    {
        // 1. Haal de OvertredingType op
        $overtredingType = OvertredingType::where('code', $overtredingCode)
            ->with('defaultStrafmaat') // Laad direct de relatie mee (Strafmaat)
            ->first();

        if (!$overtredingType) {
            // Als de overtreding code niet bestaat, gooi een exception.
            throw new \Exception("De overtreding code '{$overtredingCode}' is onbekend.");
        }
        
        // Bepaal de standaard strafmaat ID en het object.
        $defaultStrafmaatId = $overtredingType->defaultStrafmaat_id;
        $huidigeStrafmaat = $overtredingType->defaultStrafmaat;
        $geenDefaultMelding = null; // Standaard geen melding over ontbrekende default

        // Als er geen standaard strafmaat is, zoek dan de lichtste strafmaat om mee te beginnen.
        if (!$huidigeStrafmaat) {
            $huidigeStrafmaat = Strafmaat::orderBy('order_id', 'asc')->first();
            if (!$huidigeStrafmaat) {
                 // Als er helemaal geen strafmaten zijn, is er ook geen actie.
                 return [
                    'geadviseerde_strafmaat_id' => null,
                    'is_recidivist' => false,
                    'bericht' => 'Geen actie vereist. Er zijn geen strafmaten gedefinieerd in het systeem.', // TERUG NAAR 'bericht'
                    'historie_count' => 0,
                ];
            }
            $defaultStrafmaatId = $huidigeStrafmaat->id;
            $geenDefaultMelding = "Overtredingstype '{$overtredingCode}' heeft geen gekoppelde strafmaat. Start met de lichtste straf ({$huidigeStrafmaat->omschrijving}).";
        } else {
             $geenDefaultMelding = null;
        }

        // 2. Bepaal de startdatum voor de historie check
        $historieStartDatum = Carbon::now()->subMonths($lookbackMonths);
        
        // Log de parameters voor het geval de query faalt.
        Log::info('Recidive Check Parameters:', [
            'vispasnummer_input' => $vispasNummer, 
            'lookback_maanden' => $lookbackMonths, // Log de lookback
            'lookback_start' => $historieStartDatum->toDateTimeString()
        ]);

        // 3. Zoek alle relevante eerdere overtredingen (de historie) met robuuste filtering
        $query = Overtreding::where(function ($query) use ($vispasNummer) {
                // De robuuste query-logica: Visser is recidivist als het vispasnummer matcht als string OF als integer.
                
                // PRIMAIRE VERGELIJKING: Zoek naar de exacte string (VARCHAR met voorloopnullen)
                $query->where('vispasnummer', $vispasNummer)
                      ->whereNotNull('vispasnummer');
                
                // SECUNDAIRE/FALLBACK VERGELIJKING: Zoek numeriek (voor als de DB het veld als INTEGER heeft)
                if (ctype_digit($vispasNummer)) {
                    // Forceer de numerieke vergelijking om INTEGER-opgeslagen waarden te matchen.
                    // Gebruik orWhere om de flexibiliteit te behouden.
                    $query->orWhere('vispasnummer', (int)$vispasNummer);
                }
            });

        // Alleen de lookback filter toevoegen als de lookbackMonths > 0 is.
        // Dit staat een "Volledige Historie Check" toe door $lookbackMonths op 0 te zetten.
        if ($lookbackMonths > 0) {
            $query->where('created_at', '>=', $historieStartDatum);
        }
            
        $query->orderBy('created_at', 'desc');

        $eerdereOvertredingen = $query->get();
        $count = $eerdereOvertredingen->count();
        
        // Log het resultaat van de telling
        Log::info('Recidive Check Resultaat:', ['historie_count' => $count]);

        // 4. Controleer op recidive (geen eerdere overtredingen)
        if ($count === 0) {
            $bericht = $geenDefaultMelding 
                ? "{$geenDefaultMelding} Geen recidive geconstateerd." 
                : "Geen recidive geconstateerd. Standaard strafmaat ({$huidigeStrafmaat->omschrijving}) geadviseerd.";
            
            return [
                'geadviseerde_strafmaat_id' => $defaultStrafmaatId,
                'is_recidivist' => false,
                'bericht' => $bericht, // TERUG NAAR 'bericht'
                'historie_count' => 0,
            ];
        }

        // 5. Recidive is geconstateerd - Bepaal de escalatie
        
        // EERSTE CHECK: Implementatie voor de 3e overtreding (historie_count >= 2).
        // De klant wil bij de 3e keer direct de PV uitschrijven wegens hardleersheid.
        if ($count >= 2) {
            // Zoek de specifieke Strafmaat met code 'PV' (Proces-Verbaal).
            $pvStrafmaat = Strafmaat::where('code', 'PV')->first();

            if ($pvStrafmaat) {
                // De PV-strafmaat is gevonden, pas deze direct toe en overrule de order_id escalatie.
                $geescaleerdeStrafmaat = $pvStrafmaat;

                $bericht = $geenDefaultMelding
                    // Met melding over ontbrekende default
                    ? "🚨 DERDE Overtreding (of meer)! Visser is hardleers ({$count} eerdere). {$geenDefaultMelding} Directe escalatie naar '{$pvStrafmaat->omschrijving}' (PV) geadviseerd."
                    // Standaard melding
                    : "🚨 DERDE Overtreding (of meer)! Visser is hardleers ({$count} eerdere) in de laatste {$lookbackMonths} maanden. Directe escalatie naar '{$pvStrafmaat->omschrijving}' (PV) geadviseerd.";

                return [
                    'geadviseerde_strafmaat_id' => $geescaleerdeStrafmaat->id,
                    'is_recidivist' => true,
                    'bericht' => $bericht, // TERUG NAAR 'bericht'
                    'historie_count' => $count,
                ];
            }
            // Indien de 'PV'-code niet bestaat, valt de logica door naar de normale order_id escalatie hieronder.
        }
        
        // TWEEDE CHECK: Normale escalatie (voor de 2e overtreding, of als PV-code ontbreekt).
        
        // Zoek de volgende strafmaat in de escalatieketen (hogere 'order_id')
        $geescaleerdeStrafmaat = Strafmaat::where('order_id', '>', $huidigeStrafmaat->order_id)
                                          ->orderBy('order_id', 'asc') // Pak de eerstvolgende
                                          ->first();
                                          
        
        // Als er een hogere strafmaat is gevonden, escaleer
        if ($geescaleerdeStrafmaat) {
            $bericht = $geenDefaultMelding
                ? "⚠️ RECIDIVE! Visser had {$count} eerdere overtreding(en). {$geenDefaultMelding} Escalatie naar '{$geescaleerdeStrafmaat->omschrijving}' geadviseerd."
                // FIX: $1 is vervangen door $lookbackMonths
                : "⚠️ RECIDIVE! Visser had {$count} eerdere overtreding(en) in de laatste {$lookbackMonths} maanden. Escalatie naar '{$geescaleerdeStrafmaat->omschrijving}' geadviseerd.";

            return [
                'geadviseerde_strafmaat_id' => $geescaleerdeStrafmaat->id,
                'is_recidivist' => true,
                'bericht' => $bericht, // TERUG NAAR 'bericht'
                'historie_count' => $count,
            ];
        }

        // Als er geen hogere strafmaat meer is (bv. al de zwaarste), behoud de huidige
        $bericht = $geenDefaultMelding
            ? "⚠️ RECIDIVE! Visser had {$count} eerdere overtreding(en). {$geenDefaultMelding} Geen verdere escalatie mogelijk. Zwaarste maatregel ({$huidigeStrafmaat->omschrijving}) geadviseerd."
            : "⚠️ RECIDIVE! Visser had {$count} eerdere overtreding(en). Geen verdere escalatie mogelijk. Zwaarste maatregel ({$huidigeStrafmaat->omschrijving}) geadviseerd.";

        return [
            'geadviseerde_strafmaat_id' => $huidigeStrafmaat->id,
            'is_recidivist' => true,
            'bericht' => $bericht, // TERUG NAAR 'bericht'
            'historie_count' => $count,
        ];
    }
}