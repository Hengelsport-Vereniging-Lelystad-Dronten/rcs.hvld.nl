<?php

/**
 * database/migrations/2025_11_29_162110_create_controle_rondes_table.php
 *
 * Creëert de `controle_rondes` tabel die een controle-activiteit vastlegt:
 * welke gebruiker, welk water, begin/eind tijden en algemene opmerkingen.
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
// database/migrations/*_create_controle_rondes_table.php (in de up() methode)

        Schema::create('controle_rondes', function (Blueprint $table) {
            $table->id();

            // Vreemde sleutel naar de gebruiker (wie controleerde?)
            $table->foreignId('user_id')->constrained()->onDelete('restrict');

            // Vreemde sleutel naar het water (waar werd gecontroleerd?)
            $table->foreignId('water_id')->constrained()->onDelete('restrict');
            
            // Tijdstip en datum van de controle
            $table->dateTime('start_tijd');
            $table->dateTime('eind_tijd')->nullable();
            
            // Optionele opmerkingen over de gehele ronde
            $table->text('opmerkingen')->nullable(); 
            
            // Status van de ronde (bijv. concept, afgerond, geannuleerd)
            $table->string('status')->default('Concept');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_rondes');
    }
};
