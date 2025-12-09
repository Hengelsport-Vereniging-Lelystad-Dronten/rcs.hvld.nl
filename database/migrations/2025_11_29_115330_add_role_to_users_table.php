<?php

/**
 * database/migrations/2025_11_29_115330_add_role_to_users_table.php
 *
 * Voegt een `role` kolom toe aan de `users` tabel. Rollen worden gebruikt
 * voor eenvoudige autorisatie (bijv. 'Beheerder'). Deze migratie zorgt ervoor
 * dat bestaande accounts een default-rol kunnen krijgen.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Bestaande kolom uit je input
            $table->string('role')
                  ->default('Controleur')
                  ->after('password')
                  ->comment('Rol van de gebruiker: Beheerder, Coordinator, Controleur');
            
            // NIEUWE KOLOM: actief veld
            $table->boolean('actief')
                  ->default(true) // Standaard is een nieuwe gebruiker actief
                  ->after('role')
                  ->comment('Geeft aan of de gebruiker actief is');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Verwijder de nieuwe 'actief' kolom
            $table->dropColumn('actief');
            
            // Verwijder de 'role' kolom
            $table->dropColumn('role');
        });
    }
};
