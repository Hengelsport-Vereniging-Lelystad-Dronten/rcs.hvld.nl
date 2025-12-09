<?php

/**
 * database/migrations/2025_12_01_112512_add_default_strafmaat_to_overtreding_types_table.php
 *
 * Voegt een `default_strafmaat_id` kolom toe aan `overtreding_types` zodat
 * elk type direct een aanbevolen strafmaat kan verwijzen. Dit vereenvoudigt
 * de frontend selectie en backend standaardisatie.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migratie om een Foreign Key toe te voegen aan de 'overtreding_types' tabel.
 * Dit maakt het mogelijk om een standaard Strafmaat te koppelen aan elk OvertredingType,
 * wat de flow voor controleurs stroomlijnt.
 */
return new class extends Migration
{
    /**
     * Voert de migratie uit (voegt de kolom toe).
     */
    public function up(): void
    {
        // Gebruik Schema::table om een bestaande tabel aan te passen
        Schema::table('overtreding_types', function (Blueprint $table) {
            
            // Voeg de kolom 'default_strafmaat_id' toe. Deze is nullable, zodat oudere
            // overtredingstypes zonder standaard strafmaat nog steeds geldig zijn.
            $table->foreignId('default_strafmaat_id')
                  ->nullable()
                  ->comment('Verwijzing naar de standaard Strafmaat voor dit type overtreding.')
                  // Stelt de Foreign Key relatie in met de 'strafmaten' tabel.
                  ->constrained('strafmaten')
                  // Bij het verwijderen van een Strafmaat, wordt dit veld op NULL gezet
                  // in plaats van de overtredingstypes te verwijderen.
                  ->nullOnDelete();
        });
    }

    /**
     * Draait de migratie terug (verwijdert de kolom).
     */
    public function down(): void
    {
        // Gebruik Schema::table om de tabel terug te draaien
        Schema::table('overtreding_types', function (Blueprint $table) {
            
            // Verwijder eerst de Foreign Key constraint
            $table->dropConstrainedForeignId('default_strafmaat_id');
            
            // Verwijder de kolom zelf
            // Let op: Laravel's dropColumn kan de Foreign Id en de kolom in één keer droppen
            // met 'dropConstrainedForeignId', maar voor de zekerheid is het vaak beter
            // om de constraint eerst expliciet te verwijderen met dropForeign (indien nodig).
            // dropConstrainedForeignId is robuust genoeg voor deze use-case.
        });
    }
};