<?php

/**
 * database/migrations/2025_11_29_160106_create_overtreding_types_table.php
 *
 * Creëert de `overtreding_types` tabel met korte codes en uitgebreide
 * toelichtende tekst. Deze types worden gebruikt om overtredingen te categoriseren.
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
        Schema::create('overtreding_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('Officiële code (bijv. 10)');
            $table->string('omschrijving')->unique()->comment('Korte omschrijving van de overtreding');
            $table->integer('sort_order')->nullable();
            $table->text('detail_tekst')->nullable()->comment('Volledige juridische of toelichtende tekst');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtreding_types');
    }
};
