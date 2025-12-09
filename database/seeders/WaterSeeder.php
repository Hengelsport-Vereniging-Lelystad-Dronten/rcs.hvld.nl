<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * database/seeders/WaterSeeder.php
 *
 * Seeder die een set bekende wateren (locaties) invoegt met coördinaten,
 * beheersgebied en optionele beschrijving. Handig voor development en testing.
 */
class WaterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $types = [
            [
                'naam' => 'Lelystad - Zuigerplas', 
                'type' => 'Vijver', 
                'beheersgebied' => 'Sportvisserij MidWest Nederland',
                'beschrijving' => 'nachtvissen verboden. Er geldt een visverbod vanaf de westelijke oever (zie www.visplanner.nl)',
                'latitude' => '52.53617558763181',
                'longitude' => '5.47103183855966',
            ],
            [
                'naam' => 'Lelystad - Kempenaar - Gondel', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => 'Nachtviszone in De wateren woonwijk Kempenaar en Gondel',
                'latitude' => '52.5138408580956',
                'longitude' => '5.4608384554467415',
            ],
            [
                'naam' => 'Lelystad - Kempenaar - Schouw', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '',
                'latitude' => '52.513949186097264',
                'longitude' => '5.464338082378759',
            ],
            [
                'naam' => 'Lelystad - Schouw', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '',
                'latitude' => '52.5124931438829',
                'longitude' => '5.4632980457867895',
            ],
            [
                'naam' => 'Lelystad - Jol - Galjoen - Punter', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => 'Nachtviszone in De wateren woonwijk Jol en Punter: Kustpark Jol-zijde',
                'latitude' => '52.514221837093416',
                'longitude' => '5.443040957171543',
            ],
            [
                'naam' => 'Lelystad - Kanovijver - SGL', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '<h2>Water gelegen achter \'t Kofschip</h2><p>Het enige water in Lelystad alleen voor leden van de HVLD</p><ul><li>Vissen uitsluitend voor leden van de HVLD. De gratis vispas van de gemeente Lelystad is op dit water niet geldig.</li><li>Parkeren alleen op de daarvoor bestemde parkeervakken.</li><li>Voor alle vis geldt dat ze teruggezet moeten worden in hetzelfde water, uiterlijk aan het einde van de vissessie.</li><li>Open vuur verboden.</li></ul>',
                'latitude' => '52.526154494705345', 
                'longitude' => '5.472971674886496',
            ],
            [
                'naam' => 'Lelystad - Hondekop', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '',
                'latitude' => '52.49448740857137',
                'longitude' => '5.418612936041942',
            ],
            [
                'naam' => 'Lelystad - Flevopoort Artemisweg', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '',
                'latitude' => '52.480407151211104',
                'longitude' => '5.485653997930945',
            ],
            [
                'naam' => 'Lelystad - Geldersdiep', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.500330236676305',
                'longitude' => '5.505948065347628',
            ],
            [
                'naam' => 'Lelystad - Runderweg', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.51858917234177',
                'longitude' => '5.515910305637783',
            ],
            [
                'naam' => 'Lelystad - Knardijk - Lage Vaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.44822322742056',
                'longitude' => '5.43553441610969',
            ],
            [
                'naam' => 'Lelystad - Trekweg - Lage Vaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.427239273235024',
                'longitude' => '5.397663577906315',
            ],
            [
                'naam' => 'Lelystad - Torenvalkweg - Lage Dwarsvaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.47408682086546',
                'longitude' => '5.452369557675037',
            ],
            [
                'naam' => 'Lelystad - Lage Vaart - Oostervaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.505221803417385',
                'longitude' => '5.522612432983048',
            ],
            [
                'naam' => 'Lelystad - Rodemijnsteenpad', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.50194252876333',
                'longitude' => '5.528421719810786',
            ],
            [
                'naam' => 'Lelystad - Oostervaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.536376193574945',
                'longitude' => '5.525984484475429',
            ],
            [
                'naam' => 'Lelystad - Meerkoetenweg - Larservaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.476912193207205',
                'longitude' => '5.514604156286233',
            ],
            [
                'naam' => 'Lelystad - Larserweg - Hoge Vaart', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.411046965587516',
                'longitude' => '5.588109612206165',
            ],
            [
                'naam' => 'Lelystad - Plavuizenweg', 
                'type' => 'Vijver', 
                'beheersgebied' => 'HVLD',
                'beschrijving' => '',
                'latitude' => '52.56106045099131',
                'longitude' => '5.550066440235705',
            ],
            [
                'naam' => 'Lelystad - Woonhavenpad', 
                'type' => 'Vaart', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '',
                'latitude' => '52.4966127',
                'longitude' => '5.4469637',
            ],
            [
                'naam' => 'Lelystad - Houtribdreef SGL', 
                'type' => 'Sloot', 
                'beheersgebied' => 'HV Lelystad-Dronten',
                'beschrijving' => '',
                'latitude' => '52.52085840739562',
                'longitude' => '5.474375385794379',
            ]
             	
            // Wijk Golfpark
            // Het water gelegen langs de Houtribdreef
            // De wateren die de Zuiderzeewijk omringen
            // Wortmantocht
            // De wateren gelegen in de Atolwijk
            // De wateren langs de Polderdreef
            // De vaarten en tochten in de Flevopolders
            // De wateren langs de Kustdreef
            // De wateren langs de Oostranddreef
            // De wateren langs de woonwijken Zoom en Horst, Wold, Kamp, Archipel, Rozengaard, Griend.
            // De wateren langs de Zuigerplasdreef
            // Wateren Lelystadcentrum-zuid
            // De wateren gelegen in de Landerijen
            // De wateren gelegen in de Waterwijk
            // Flevopoort1
            // Torenvalktocht
            // De wateren gelegen in de Landstrekenwijk
            // Het Havendiep
            // De wateren in de woonwijken Botter, Schoener en Tjalk
            // Wijk Noordersluis
            // Zeilhaven en haven Kraanweg ged.
            // Lage Dwarsvaart
            // Het Bovenwater
            // Wijk Galjoen
            // De vaarten en tochten in de Flevopolders

        ];

        DB::table('waters')->insert($types);
    }
}