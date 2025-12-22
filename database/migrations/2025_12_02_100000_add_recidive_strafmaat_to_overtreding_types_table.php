<?php

/**
 * database/migrations/2025_12_02_100000_add_recidive_strafmaat_to_overtreding_types_table.php
 *
 * Voegt een optioneel `recidive_strafmaat_id` veld toe aan `overtreding_types`
 * zodat er een speciale strafmaat gebruikt kan worden bij recidivegevallen.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migratie om een Foreign Key toe te voegen aan de 'overtreding_types' tabel voor recidive.
 * Dit maakt het mogelijk om een recidive Strafmaat te koppelen aan elk OvertredingType.
 */
return new class extends Migration
{
    /**
     * Voert de migratie uit (voegt de kolom toe).
     */
    public function up(): void
    {
        Schema::table('overtreding_types', function (Blueprint $table) {
            $table->foreignId('recidive_strafmaat_id')
                  ->nullable()
                  ->comment('Verwijzing naar de recidive Strafmaat voor dit type overtreding.')
                  ->constrained('strafmaten')
                  ->nullOnDelete();
        });
    }

    /**
     * Draait de migratie terug (verwijdert de kolom).
     */
    public function down(): void
    {
        Schema::table('overtreding_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recidive_strafmaat_id');
        });
    }
};
