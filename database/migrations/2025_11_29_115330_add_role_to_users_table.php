<?php

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
