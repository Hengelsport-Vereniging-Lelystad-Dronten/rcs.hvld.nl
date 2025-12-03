<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder voor het vullen van de 'overtreding_types' tabel met initiële data.
 *
 * BELANGRIJK: De 'default_strafmaat_id' en nieuwe 'recidive_strafmaat_id' kolommen
 * zijn bijgewerkt op basis van de aangeleverde structuur.
 *
 * Controleer de mapping met de StrafmaatSeeder:
 * ID 1 = WA (Waarschuwing)
 * ID 2 = HG (Herstelgesprek)
 * ID 3 = VPA-ZT (Zeer Tijdelijke Inname - Veldactie)
 * ID 4 = VPA-T (Tijdelijke Inname - Formele Tuchtmaatregel)
 * ID 10 = PV (Proces-verbaal, indien dit ID 10 is in StrafmaatSeeder)
 */
class OvertredingTypeSeeder extends Seeder
{
    /**
     * Voert de database seeds uit.
     */
    public function run(): void
    {
        $types = [
            // Overtreding 00: Geen overtreding(en)
            // Default: null. Recidive: null
            ['code' => '00', 'omschrijving' => 'Geen overtreding(en)', 'detail_tekst' => 'De visser voldoet aan alle regels.', 'default_strafmaat_id' => null, 'recidive_strafmaat_id' => null],

            // Overtreding 10: Géén schriftelijke toestemming (ID 2 in YAML)
            // Default: ID 1 (WA). Recidive: ID 10 (PV of JUS - Afhankelijk van StrafmaatSeeder)
            ['code' => '10', 'omschrijving' => 'Géén schriftelijke toestemming', 'detail_tekst' => 'VISpas of andere geldige documenten niet getoond.', 'default_strafmaat_id' => 1, 'recidive_strafmaat_id' => 10],

            // Overtreding 15: Vissen zonder nachtvistoestemming (ID 3 in YAML)
            // Default: ID 1 (WA). Recidive: ID 4 (VPA-T)
            ['code' => '15', 'omschrijving' => 'Vissen zonder nachtvistoestemming', 'detail_tekst' => 'Vissen in de nacht zonder de benodigde toestemming.', 'default_strafmaat_id' => 1, 'recidive_strafmaat_id' => 4],

            // Overtreding 20: Vissen met meer hengels dan toegestaan (ID 4 in YAML)
            // Default: ID 1 (WA). Recidive: ID 4 (VPA-T)
            ['code' => '20', 'omschrijving' => 'Vissen met meer hengels dan toegestaan', 'detail_tekst' => 'Vissen met meer hengels dan de vergunning toestaat.', 'default_strafmaat_id' => 1, 'recidive_strafmaat_id' => 4],

            // Overtreding 25: Achterlaten van afval (ID 5 in YAML)
            // Default: ID 1 (WA). Recidive: ID 2 (HG)
            ['code' => '25', 'omschrijving' => 'Achterlaten van vismateriaal en ander afval', 'detail_tekst' => 'Afval achterlaten op de visstek.', 'default_strafmaat_id' => 1, 'recidive_strafmaat_id' => 2],

            // Overtreding 30: Maken van kampvuur of BBQ (ID 6 in YAML)
            // Default: ID 1 (WA). Recidive: ID 2 (HG)
            ['code' => '30', 'omschrijving' => 'Maken van kampvuur of BBQ', 'detail_tekst' => 'Open vuur of barbecueën op een verboden plaats.', 'default_strafmaat_id' => 1, 'recidive_strafmaat_id' => 2],

            // Overtreding 35: Weigering medewerking verlenen (ID 7 in YAML)
            // Default: ID 10 (PV of JUS). Recidive: ID 10 (PV of JUS)
            ['code' => '35', 'omschrijving' => 'Weigering medewerking verlenen', 'detail_tekst' => 'Weigering medewerking aan controleur.', 'default_strafmaat_id' => 10, 'recidive_strafmaat_id' => 10],

            // Overtreding 40: In bezit/meenemen teveel en/of niet toegestane vis (ID 8 in YAML)
            // Default: ID 3 (VPA-ZT). Recidive: ID 10 (PV of JUS)
            ['code' => '40', 'omschrijving' => 'In bezit/meenemen teveel en/of niet toegestane vis', 'detail_tekst' => 'Overschrijding van de toegestane maat of aantal vis in bezit.', 'default_strafmaat_id' => 3, 'recidive_strafmaat_id' => 10],

            // Overtreding 45: Gesloten tijd aassoorten (ID 9 in YAML)
            // Default: ID 3 (VPA-ZT). Recidive: ID 10 (PV of JUS)
            ['code' => '45', 'omschrijving' => 'Gesloten tijd aassoorten', 'detail_tekst' => 'Vissen met aassoorten die verboden zijn tijdens de gesloten tijd.', 'default_strafmaat_id' => 3, 'recidive_strafmaat_id' => 10],

            // Overtreding 50: Vissen met levend aas (ID 10 in YAML)
            // Default: ID 4 (VPA-T). Recidive: ID 10 (PV of JUS)
            ['code' => '50', 'omschrijving' => 'Vissen met levend aas', 'detail_tekst' => 'Het gebruik van levend aas.', 'default_strafmaat_id' => 4, 'recidive_strafmaat_id' => 10],

            // Overtreding 55: Overige overtreding (ID 11 in YAML)
            // Default: null. Recidive: null
            ['code' => '55', 'omschrijving' => 'Overige overtreding', 'detail_tekst' => 'Overtreding die elders niet gecategoriseerd is (opmerkingen verplicht).', 'default_strafmaat_id' => null, 'recidive_strafmaat_id' => null],
        ];

        DB::table('overtreding_types')->insert($types);
    }
}