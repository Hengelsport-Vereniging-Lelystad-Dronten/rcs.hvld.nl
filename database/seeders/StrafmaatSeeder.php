<?php

namespace Database\Seeders;

use App\Models\Strafmaat; // Importeer het Strafmaat Model
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon; // Importeer Carbon voor een schone datum

/**
 * Seeder voor het vullen van de 'strafmaten' tabel met initiële, standaard data.
 * Nu inclusief de 'order_id' om een vaste, handmatig gedefinieerde sorteervolgorde
 * te garanderen voor dropdowns en overzichten in de hele applicatie.
 */
class StrafmaatSeeder extends Seeder
{
    /**
     * Voert de database seeds uit.
     */
    public function run(): void
    {
        // De huidige tijd, te gebruiken voor alle timestamps
        $now = Carbon::now();
        // Counter voor de oplopende order_id (begint bij 1)
        $orderIdCounter = 1;

        // Array met alle standaard strafmaten, gecategoriseerd voor helderheid.
        // * * BELANGRIJK: De volgorde in deze array bepaalt de uiteindelijke order_id. * *
        $strafmatenData = [

            // -------------------------------------
            // INTERNE / VERENIGINGSMAATREGELEN (Prioriteit 1 - 5)
            // -------------------------------------

            [
                'code' => 'WA',
                'omschrijving' => 'Waarschuwing (mondeling of officieel), met optionele aantekening in het verenigingsdossier.',
            ],
            [
                'code' => 'HG',
                'omschrijving' => 'Herstelgesprek verplicht met bestuur na overtreding (educatief, geen harde straf).',
            ],
            [
                'code' => 'TS',
                'omschrijving' => 'Tijdelijke schorsing van visrechten op verenigingswater voor bepaalde periode.',
            ],
            [
                'code' => 'VPA',
                'omschrijving' => 'Inname en intrekking van VISpas/visdocumenten wegens ernstige overtreding.',
            ],
            [
                'code' => 'POV',
                'omschrijving' => 'Permanent ontzeggen van toegang tot verenigingswateren (ernstige/meervoudige overtredingen).',
            ],
            
            // -------------------------------------
            // OPERATIONELE ACTIES (GEEN STRAF, MAAR WEL REGISTREREN/MELDEN) (Prioriteit 6 - 8)
            // -------------------------------------
            
            [
                'code' => 'OKE',
                'omschrijving' => 'Opmaken intern controleverslag met foto’s / bewijsmateriaal (administratieve actie).',
            ],
            [
                'code' => 'MA',
                'omschrijving' => 'Melding aan terreinbeheerder (SBB, NM) bij natuur- of gedragsgerelateerde overtredingen.',
            ],
            [
                'code' => 'MWB',
                'omschrijving' => 'Melding aan waterschap bij overtredingen betreffende oevers, beschoeiing of waterkwaliteit.',
            ],

            // -------------------------------------
            // OFFICIËLE/WETTELIJKE SANCTIES (via BOA/Politie) (Prioriteit 9 - 10)
            // -------------------------------------

            [
                'code' => 'MJ',
                'omschrijving' => 'Melding aan Justitie / politie / BOA’s bij strafbare feiten of zware visserijovertredingen.',
            ],
            [
                'code' => 'BOA',
                'omschrijving' => 'Overdracht van zaak aan BOA’s voor mogelijk proces-verbaal, boete of verdere afhandeling.',
            ],
        ];

        // Doorloop de array en voeg order_id en timestamps toe aan elk record
        $strafmatenToInsert = [];
        foreach ($strafmatenData as $strafmaat) {
            // HIER WORDT DE ORDER_ID TOEGEVOEGD:
            $strafmaat['order_id'] = $orderIdCounter++; 
            $strafmaat['created_at'] = $now;
            $strafmaat['updated_at'] = $now;
            $strafmatenToInsert[] = $strafmaat;
        }


        // Voeg de data toe aan de database via de insert methode van het model.
        Strafmaat::insert($strafmatenToInsert);
    }
}