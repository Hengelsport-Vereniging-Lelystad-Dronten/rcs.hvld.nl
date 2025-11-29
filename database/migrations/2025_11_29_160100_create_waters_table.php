<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migratie voor het aanmaken van de 'waters' tabel.
 * Deze versie gebruikt het ENUM-type voor 'type', TEXT voor 'beschrijving' en
 * DECIMAL(10, 7) voor de GPS-coördinaten voor optimale precisie.
 */
return new class extends Migration
{
    /**
     * Voert de migratie uit (creëert de tabel 'waters').
     */
    public function up(): void
    {
        Schema::create('waters', function (Blueprint $table) {
            $table->id();
            
            // Naam van het water. Uniek om dubbele invoer te voorkomen.
            $table->string('naam')->unique()->comment('Naam van het viswater (bijv. Grote Plas)');
            
            // Beperkt de water type tot een vaste set van waarden voor data-integriteit.
            $table->enum('type', ['Meer', 'Rivier', 'Kanaal', 'Gracht', 'Vijver', 'Sloot', 'Vaart', 'Anders'])->comment('Gespecificeerd type water.');
            
            // Het beheersgebied, bijvoorbeeld 'HV Lelystad-Dronten'. Optioneel.
            $table->string('beheersgebied')->nullable()->comment('Het beheersgebied, bijvoorbeeld \'HV Lelystad-Dronten\'. Optioneel.');
            
            // Gedetailleerde beschrijving en visregels van het water.
            // Gebruikt TEXT om lange HTML-inhoud te accommoderen, wat de vorige fout voorkomt.
            $table->text('beschrijving')->nullable()->comment('Gedetailleerde beschrijving van het water, inclusief regels.'); 
            
            // GPS-coördinaten voor kaartweergave. Aangepast naar DECIMAL(10, 7) voor hoge nauwkeurigheid.
            $table->decimal('latitude', 10, 7)->nullable()->comment('Breedtegraad voor geolocatie (DECIMAL 10, 7).');
            $table->decimal('longitude', 10, 7)->nullable()->comment('Lengtegraad voor geolocatie (DECIMAL 10, 7).');

            $table->timestamps();
        });
    }

    /**
     * Draait de migratie terug (verwijdert de tabel 'waters').
     */
    public function down(): void
    {
        Schema::dropIfExists('waters');
    }
};