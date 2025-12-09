<?php

namespace Database\Seeders;

use App\Models\Strafmaat; // Importeer het Strafmaat Model
use Illuminate\Database\Seeder;
// Geen Carbon import nodig, we gebruiken de Laravel helper now()

/**
 * database/seeders/StrafmaatSeeder.php
 *
 * Seeder die standaard strafmaten aanmaakt met een expliciete `order_id`.
 * De `order_id` wordt gebruikt in de UI om consistentie in dropdowns en lijsten
 * te garanderen. Pas deze seeder aan wanneer je de volgorde van strafmaten
 * wilt wijzigen.
 */
class StrafmaatSeeder extends Seeder
{
    /**
     * Voert de database seeds uit.
     */
    public function run(): void
    {
        // De huidige tijd via de Laravel helper, te gebruiken voor alle timestamps
        $now = now();
        // Counter voor de oplopende order_id (begint bij 1). Deze garandeert de volgorde.
        $orderIdCounter = 1;

        // Array met alle standaard strafmaten, gecategoriseerd voor helderheid en onderhoud.
        // * * BELANGRIJK: De volgorde in deze array bepaalt de uiteindelijke order_id in de database. * *
        $strafmatenData = [

            // -------------------------------------
            // INTERNE / VERENIGINGSMAATREGELEN (Prioriteit 1 - 6)
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
                'code' => 'VPA-ZT',
                'omschrijving' => 'Zeer Tijdelijke Inname en intrekking van VISpas/visdocumenten (1 week, veldactie ter vastlegging).',
            ],
            [
                'code' => 'VPA-T',
                'omschrijving' => 'Tijdelijke Inname en intrekking van VISpas/visdocumenten (bijv. 3-12 maanden na bestuurlijke afhandeling).',
            ],
            [
                'code' => 'VPA-P',
                'omschrijving' => 'Permanente Inname en intrekking van VISpas/visdocumenten (kan clubbreed of via de landelijke Tuchtcommissie worden geregistreerd).',
            ],
            [
                'code' => 'RL',
                'omschrijving' => 'Ontbinding van het lidmaatschap (definitieve beëindiging).',
            ],

            // -------------------------------------
            // OPERATIONELE ACTIES (GEEN STRAF, MAAR WEL REGISTREREN/MELDEN) (Prioriteit 7 - 9)
            // -------------------------------------

            [
                'code' => 'OKE',
                'omschrijving' => 'Opmaken intern controleverslag met foto’s / bewijsmateriaal (administratieve actie t.b.v. dossier).',
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
            // OFFICIËLE/WETTELIJKE SANCTIES (via BOA/Politie) (Prioriteit 10 - 11)
            // -------------------------------------

            [
                'code' => 'PV',
                'omschrijving' => 'Proces-verbaal opgemaakt door BOA/Politie (directe wettelijke boete/straf).',
            ],
            [
                'code' => 'JUS',
                'omschrijving' => 'Melding aan Justitie bij strafbare feiten of zware visserijovertredingen (politie-inzet vereist).',
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