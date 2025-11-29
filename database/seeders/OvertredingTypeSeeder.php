<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OvertredingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            // Dit is GEEN overtreding, dus geen standaard strafmaat
            ['code' => '00', 'omschrijving' => 'Geen overtreding(en)', 'detail_tekst' => 'De visser voldoet aan alle regels.', 'default_strafmaat_id' => null],
            
            // Overtreding 10: Standaard een Rapportering/Officiële waarschuwing (ID 2)
            ['code' => '10', 'omschrijving' => 'Géén schriftelijke toestemming', 'detail_tekst' => 'VISpas of andere geldige documenten niet getoond.', 'default_strafmaat_id' => 2],
            
            // Overtreding 15: Standaard een Mondelinge waarschuwing (ID 1)
            ['code' => '15', 'omschrijving' => 'Vissen zonder nachtvistoestemming', 'detail_tekst' => 'Vissen in de nacht zonder de benodigde toestemming.', 'default_strafmaat_id' => 1],
            
            ['code' => '20', 'omschrijving' => 'Vissen met meer hengels dan toegestaan', 'detail_tekst' => 'Vissen met meer hengels dan de vergunning toestaat.', 'default_strafmaat_id' => 1],
            ['code' => '25', 'omschrijving' => 'Achterlaten van vismateriaal en ander afval', 'detail_tekst' => 'Afval achterlaten op de visstek.', 'default_strafmaat_id' => 1],
            ['code' => '30', 'omschrijving' => 'Maken van kampvuur of BBQ', 'detail_tekst' => 'Open vuur of barbecueën op een verboden plaats.', 'default_strafmaat_id' => 1],
            
            // Overtreding 35: Weigering is ernstig, dus standaard een Officiële waarschuwing (ID 2)
            ['code' => '35', 'omschrijving' => 'Weigering medewerking verlenen', 'detail_tekst' => 'Weigering medewerking aan controleur.', 'default_strafmaat_id' => 2],
            
            ['code' => '40', 'omschrijving' => 'In bezit/meenemen teveel en/of niet toegestane vis', 'detail_tekst' => 'Overschrijding van de toegestane maat of aantal vis in bezit.', 'default_strafmaat_id' => 1],
            ['code' => '45', 'omschrijving' => 'Gesloten tijd aassoorten', 'detail_tekst' => 'Vissen met aassoorten die verboden zijn tijdens de gesloten tijd.', 'default_strafmaat_id' => 1],
            ['code' => '50', 'omschrijving' => 'Vissen met levend aas', 'detail_tekst' => 'Het gebruik van levend aas.', 'default_strafmaat_id' => 1],
            ['code' => '55', 'omschrijving' => 'Overige overtreding', 'detail_tekst' => 'Overtreding die elders niet gecategoriseerd is (opmerkingen verplicht).', 'default_strafmaat_id' => null],
        ];

        DB::table('overtreding_types')->insert($types);
    }
}