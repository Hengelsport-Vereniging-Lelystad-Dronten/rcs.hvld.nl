<?php

/**
 * database/migrations/2025_12_01_103420_create_strafmaten_table.php
 *
 * Creëert de `strafmaten` tabel met een unieke `order_id` zodat de volgorde
 * in dropdowns en lijsten consistent en handmatig te bepalen is.
 * De tabel bevat een korte code, omschrijving en timestamps.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Voert de migratie uit (Creëert de tabel).
     * * Deze methode definieert de structuur van de 'strafmaten' tabel in de database.
     */
    public function up(): void
    {
        // Start met het aanmaken van de 'strafmaten' tabel
        Schema::create('strafmaten', function (Blueprint $table) {
            // De standaard primaire sleutel (auto-incrementing ID)
            $table->id();
            
            // Korte, unieke interne code (optioneel, vandaar nullable())
            // Dit veld is bedoeld voor snelle, interne referentie, bijv. 'WA' voor Waarschuwing.
            // Het is uniek om duplicatie van codes te voorkomen.
            $table->string('code', 50)->nullable()->unique()->comment('Korte unieke interne code, bijv. WA of IS');
            
            // * * NIEUW VELD: ORDER_ID * *
            // Dit veld garandeert een vaste, handmatige sorteervolgorde voor de hele applicatie (bijv. in dropdowns).
            // Het is uniek om te voorkomen dat twee strafmaten dezelfde positie innemen.
            $table->unsignedSmallInteger('order_id')->unique()->comment('Handmatig gedefinieerde, unieke sorteervolgorde.');
            
            // De volledige omschrijving van de strafmaat (verplicht)
            // Gebruik 'text' omdat de omschrijving langer kan zijn dan een string.
            $table->text('omschrijving')->comment('Volledige omschrijving van de strafmaat, bijv. Inbeslagname vergunning');
            
            // Timestamps: 'created_at' en 'updated_at'
            $table->timestamps();
        });
    }

    /**
     * Draait de migratie terug (Verwijdert de tabel).
     * * Deze methode wordt uitgevoerd wanneer de gebruiker 'php artisan migrate:rollback' uitvoert.
     */
    public function down(): void
    {
        // Controleert of de tabel bestaat en verwijdert deze indien nodig
        Schema::dropIfExists('strafmaten');
    }
};